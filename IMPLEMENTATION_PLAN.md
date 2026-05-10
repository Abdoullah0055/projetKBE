# Darquest - Implementation Plan (Sprint 1-3 Gaps)

> Status: All ⚠️ partial and ❌ not-done items from the audit, with exact file paths, line numbers, code snippets, and step-by-step instructions for each change.

---

## TABLE OF CONTENTS

1. [F1 - Currency Conversion at Checkout](#f1---currency-conversion-at-checkout)
2. [F2 - Item Price Validation (1-100 or)](#f2---item-price-validation-1-100-or)
3. [F3 - Sort Controls on Catalog (index.php)](#f3---sort-controls-on-catalog-indexphp)
4. [F4 - Rarity + Price Filters on Catalog](#f4---rarity--price-filters-on-catalog)
5. [F5 - Mage Check at Checkout (confirmer_achat.php)](#f5---mage-check-at-checkout-confirmer_achatphp)
6. [F6 - Comment Field on Reviews](#f6---comment-field-on-reviews)
7. [F7 - Player Deletes Own Review](#f7---player-deletes-own-review)
8. [F8 - Admin Deletes Player Review](#f8---admin-deletes-player-review)
9. [F9 - Star Distribution Percentage Display](#f9---star-distribution-percentage-display)
10. [F10 - Admin Capital Increase (FundsGivenCount logic)](#f10---admin-capital-increase-fundsgivencount-logic)
11. [F11 - Admin View Player Inventories](#f11---admin-view-player-inventories)
12. [F12 - Inventory Filtering (optional)](#f12---inventory-filtering-optional)
13. [F13 - Smart Potion/Spell Use (prevent waste)](#f13---smart-potionspell-use-prevent-waste)
14. [F14 - Enigma TrueFalse Type](#f14---enigma-truefalse-type)
15. [F15 - Enigma ShortAnswer Type](#f15---enigma-shortanswer-type)
16. [F16 - Statistics as Graph/Chart](#f16---statistics-as-graphchart)
17. [F17 - MaxHP UI in Profile](#f17---maxhp-ui-in-profile)

---

## F1 - Currency Conversion at Checkout

### Spec
When a player checks out and lacks sufficient silver or bronze, the system auto-converts from higher currency:
- 1 Gold = 10 Silver
- 1 Silver = 10 Bronze
- Debit lowest currency first, then convert upward as needed.

### Files to Modify
- `backend/confirmer_achat.php` (lines 89-113)

### Current Code (lines 89-113)
```php
$totalGold = 0;
$totalSilver = 0;
$totalBronze = 0;

foreach ($cartItems as $item) {
    // ... validation ...
    $totalGold += (int)$item['pricegold'] * $quantity;
    $totalSilver += (int)$item['pricesilver'] * $quantity;
    $totalBronze += (int)$item['pricebronze'] * $quantity;
}

if ((int)$userRow['gold'] < $totalGold || (int)$userRow['silver'] < $totalSilver || (int)$userRow['bronze'] < $totalBronze) {
    fail_checkout($pdo, 'Solde insuffisant pour finaliser l\'achat.', 'balance_insufficient');
}
```

### Replacement Code
Replace lines 89-113 with:

```php
$totalGold = 0;
$totalSilver = 0;
$totalBronze = 0;

foreach ($cartItems as $item) {
    $quantity = (int)$item['quantity'];
    $stock = (int)$item['stock'];
    $isActive = (int)$item['isactive'] === 1;

    if (!$isActive) {
        fail_checkout($pdo, 'Un article de votre panier n\'est plus disponible.', 'inactive_item');
    }

    if ($quantity <= 0 || $quantity > $stock) {
        fail_checkout($pdo, 'Stock insuffisant pour finaliser l\'achat.', 'stock_insufficient');
    }

    $totalGold += (int)$item['pricegold'] * $quantity;
    $totalSilver += (int)$item['pricesilver'] * $quantity;
    $totalBronze += (int)$item['pricebronze'] * $quantity;
}

$userGold = (int)$userRow['gold'];
$userSilver = (int)$userRow['silver'];
$userBronze = (int)$userRow['bronze'];

$needBronze = max(0, $totalBronze - $userBronze);
$convertSilverToBronze = 0;
if ($needBronze > 0) {
    $convertSilverToBronze = (int)ceil($needBronze / 10);
    if ($userSilver < $convertSilverToBronze + $totalSilver) {
        $silverShortfall = ($convertSilverToBronze + $totalSilver) - $userSilver;
        $convertGoldToSilver = (int)ceil($silverShortfall / 10);
        if ($userGold < $convertGoldToSilver + $totalGold) {
            fail_checkout($pdo, 'Solde insuffisant pour finaliser l\'achat.', 'balance_insufficient');
        }
        $debitGold = $totalGold + $convertGoldToSilver;
        $remainingSilver = $convertGoldToSilver * 10 - $silverShortfall;
        $debitSilver = $totalSilver + $convertSilverToBronze - $remainingSilver;
        $debitBronze = 0;
    } else {
        $debitGold = $totalGold;
        $debitSilver = $totalSilver + $convertSilverToBronze;
        $debitBronze = 0;
    }
} else {
    $surplusBronze = $userBronze - $totalBronze;
    $needSilver = max(0, $totalSilver - $userSilver);
    if ($needSilver > 0) {
        $fromBronze = min((int)floor($surplusBronze / 10), $needSilver);
        $needSilver -= $fromBronze;
        $surplusBronze -= $fromBronze * 10;
    }
    if ($needSilver > 0) {
        $convertGoldToSilver = (int)ceil($needSilver / 10);
        if ($userGold < $convertGoldToSilver + $totalGold) {
            fail_checkout($pdo, 'Solde insuffisant pour finaliser l\'achat.', 'balance_insufficient');
        }
        $debitGold = $totalGold + $convertGoldToSilver;
        $remainingSilver = $convertGoldToSilver * 10 - $needSilver;
        $debitSilver = max(0, $totalSilver - $userSilver - $fromBronze) > 0 ? 0 : max(0, $totalSilver - $fromBronze);
        if ($remainingSilver > 0 && $totalSilver > $fromBronze) {
            $debitSilver = max(0, $totalSilver - $fromBronze - $remainingSilver);
        } elseif ($remainingSilver > 0) {
            $debitSilver = 0;
        } else {
            $debitSilver = max(0, $totalSilver - $fromBronze);
        }
        $debitBronze = $totalBronze;
    } else {
        $debitGold = $totalGold;
        $debitSilver = max(0, $totalSilver - $fromBronze);
        $debitBronze = $totalBronze + ($fromBronze * 10);
    }
}
```

**Wait — the above logic is getting complex. Let me provide a cleaner algorithm:**

### Cleaner Algorithm

Replace lines 89-113 with this simplified version:

```php
$totalGold = 0;
$totalSilver = 0;
$totalBronze = 0;

foreach ($cartItems as $item) {
    $quantity = (int)$item['quantity'];
    $stock = (int)$item['stock'];
    $isActive = (int)$item['isactive'] === 1;

    if (!$isActive) {
        fail_checkout($pdo, 'Un article de votre panier n\'est plus disponible.', 'inactive_item');
    }

    if ($quantity <= 0 || $quantity > $stock) {
        fail_checkout($pdo, 'Stock insuffisant pour finaliser l\'achat.', 'stock_insufficient');
    }

    $totalGold += (int)$item['pricegold'] * $quantity;
    $totalSilver += (int)$item['pricesilver'] * $quantity;
    $totalBronze += (int)$item['pricebronze'] * $quantity;
}

$userGold = (int)$userRow['gold'];
$userSilver = (int)$userRow['silver'];
$userBronze = (int)$userRow['bronze'];

// Normalize everything to bronze for affordability check
$totalInBronze = $totalGold * 100 + $totalSilver * 10 + $totalBronze;
$userInBronze = $userGold * 100 + $userSilver * 10 + $userBronze;

if ($userInBronze < $totalInBronze) {
    fail_checkout($pdo, 'Solde insuffisant pour finaliser l\'achat.', 'balance_insufficient');
}

// Calculate actual debit amounts (debit lowest currency first, convert upward)
$remainingBronze = $totalBronze;
$debitBronze = min($remainingBronze, $userBronze);
$remainingBronze -= $debitBronze;

// If still need bronze, convert silver -> bronze (1 silver = 10 bronze)
$convertSilverToBronze = 0;
if ($remainingBronze > 0) {
    $convertSilverToBronze = (int)ceil($remainingBronze / 10);
}

$remainingSilver = $totalSilver + $convertSilverToBronze;
$debitSilver = min($remainingSilver, $userSilver);
$remainingSilver -= $debitSilver;

// If still need silver, convert gold -> silver (1 gold = 10 silver)
$convertGoldToSilver = 0;
if ($remainingSilver > 0) {
    $convertGoldToSilver = (int)ceil($remainingSilver / 10);
}

$debitGold = $totalGold + $convertGoldToSilver;
```

Then replace the debit UPDATE (lines 173-185) to use `$debitGold`, `$debitSilver`, `$debitBronze` instead of `$totalGold`, `$totalSilver`, `$totalBronze`:

```php
$debitStmt = $pdo->prepare(
    "UPDATE Users
    SET Gold = Gold - :gold, Silver = Silver - :silver, Bronze = Bronze - :bronze
    WHERE UserId = :userId"
);
$debitStmt->execute([
    ':gold' => $debitGold,
    ':silver' => $debitSilver,
    ':bronze' => $debitBronze,
    ':userId' => $userId,
]);

$pdo->commit();

$_SESSION['user']['gold'] = (int)$userRow['gold'] - $debitGold;
$_SESSION['user']['silver'] = (int)$userRow['silver'] - $debitSilver;
$_SESSION['user']['bronze'] = (int)$userRow['bronze'] - $debitBronze;

respond_json([
    'success' => true,
    'message' => 'Achat confirme avec succes.',
    'order_id' => $orderId,
    'totals' => [
        'gold' => $totalGold,
        'silver' => $totalSilver,
        'bronze' => $totalBronze,
    ],
    'conversion' => [
        'gold_to_silver' => $convertGoldToSilver,
        'silver_to_bronze' => $convertSilverToBronze,
    ],
]);
```

### Step-by-Step
1. Open `backend/confirmer_achat.php`
2. Replace lines 89-113 with the "Cleaner Algorithm" block above
3. Replace lines 173-202 with the updated debit + response block above
4. Test: create a user with 1 gold, 0 silver, 0 bronze; try buying an item costing 0 gold, 5 silver, 0 bronze → should auto-convert 1 gold → 10 silver, debit 1 gold and 5 silver

---

## F2 - Item Price Validation (1-100 or)

### Spec
Items must have a total price between 1 and 100 gold equivalent. Items priced at 0 in all currencies or over 100 gold should be rejected.

### Files to Modify
- `admin.php` — lines 18-49 (add_item action)
- `admin.php` — lines 53-61 (edit_item action)

### Changes

#### In add_item (after line 21, before the try block):
```php
$totalGoldEquiv = $gold + ($silver / 10) + ($bronze / 100);
if ($totalGoldEquiv < 1 || $totalGoldEquiv > 100) {
    $message_alerte = ["type" => "erreur", "texte" => "Le prix total doit etre entre 1 et 100 pieces d'or equivalent."];
    // Skip the insert by wrapping the try block in an else
}
```

Wrap the existing try block (lines 23-49) inside an `if (!$message_alerte) { ... }` block.

#### In edit_item (after line 57, before the UPDATE):
```php
$totalGoldEquiv = $gold + ($silver / 10) + ($bronze / 100);
if ($totalGoldEquiv < 1 || $totalGoldEquiv > 100) {
    $message_alerte = ["type" => "erreur", "texte" => "Le prix total doit etre entre 1 et 100 pieces d'or equivalent."];
} else {
    // existing UPDATE code
}
```

### Step-by-Step
1. Open `admin.php`
2. After line 21 (`$bronze = (int)$_POST['bronze'];`), add the validation check
3. Wrap the try block (lines 23-49) inside `if (empty($message_alerte)) { ... }`
4. After line 57 in edit_item, add the same validation, wrap the UPDATE in an else
5. Also add `min="1"` to the Gold input on the add_item form (line 361) to hint at minimum
6. Test: try adding an item with 0/0/0 price → should get error message

---

## F3 - Sort Controls on Catalog (index.php)

### Spec
Add a sort dropdown with options:
- Nom (A-Z / Z-A)
- Prix (asc / desc)
- Note moyenne (desc / asc)
- Rareté (Commun→Mythique / Mythique→Commun)

### Files to Modify
- `index.php` — sidebar filter section (around lines 471-494), add sort dropdown
- `index.php` — JS `applyFilters()` function (around lines 635-657), add sort logic
- `index.php` — add `data-price`, `data-rating`, `data-rarity-order` attributes to item-row divs (around line 536)

### Changes

#### 1. Add data attributes to each item-row (line 536 area)
Current:
```html
<div
 class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
 data-type="<?= htmlspecialchars($normType) ?>"
 data-name="<?= htmlspecialchars(mb_strtolower($item['nom'], 'UTF-8')) ?>"
 data-rarity="<?= htmlspecialchars($rarityClass) ?>"
 onclick="window.location.href='details.php?id=<?= (int)$item['id'] ?>'">
```

New (add data-price, data-rating, data-rarity-order):
```html
<?php
$rarityOrderMap = ['commun'=>1, 'rare'=>2, 'epique'=>3, 'legendaire'=>4, 'mythique'=>5];
$rarityOrder = $rarityOrderMap[normalizeRarityValue((string)($item['rarete'] ?? 'Commun'))] ?? 1;
?>
<div
 class="item-row <?= ($item['stock'] == 0) ? 'item-out-of-stock' : '' ?> <?= htmlspecialchars($rarityClass) ?>"
 data-type="<?= htmlspecialchars($normType) ?>"
 data-name="<?= htmlspecialchars(mb_strtolower($item['nom'], 'UTF-8')) ?>"
 data-rarity="<?= htmlspecialchars($rarityClass) ?>"
 data-price="<?= (float)$item['prix'] ?>"
 data-rating="<?= (float)$item['rating'] ?>"
 data-rarity-order="<?= $rarityOrder ?>"
 onclick="window.location.href='details.php?id=<?= (int)$item['id'] ?>'">
```

#### 2. Add sort dropdown in sidebar (after the type filter, around line 486)
```html
<div class="filter-group" style="margin-top:15px;">
    <label>Trier par</label>
    <select id="sort-filter" class="filter-select">
        <option value="name-asc">Nom (A-Z)</option>
        <option value="name-desc">Nom (Z-A)</option>
        <option value="price-asc">Prix (croissant)</option>
        <option value="price-desc">Prix (decroissant)</option>
        <option value="rating-desc">Note (meilleure)</option>
        <option value="rating-asc">Note (moins bonne)</option>
        <option value="rarity-asc">Rarrete (Commun -> Mythique)</option>
        <option value="rarity-desc">Rarrete (Mythique -> Commun)</option>
    </select>
</div>
```

#### 3. Add sort logic to JS (in the applyFilters function)
Add after the `applyFilters` function definition:

```javascript
function sortItems() {
    const sortValue = sortFilter.value;
    const [sortKey, sortDir] = sortValue.split('-');
    const itemsArray = Array.from(items);

    itemsArray.sort((a, b) => {
        let valA, valB;
        switch (sortKey) {
            case 'name':
                valA = a.dataset.name || '';
                valB = b.dataset.name || '';
                return sortDir === 'asc' ? valA.localeCompare(valB) : valB.localeCompare(valA);
            case 'price':
                valA = parseFloat(a.dataset.price) || 0;
                valB = parseFloat(b.dataset.price) || 0;
                return sortDir === 'asc' ? valA - valB : valB - valA;
            case 'rating':
                valA = parseFloat(a.dataset.rating) || 0;
                valB = parseFloat(b.dataset.rating) || 0;
                return sortDir === 'asc' ? valA - valB : valB - valA;
            case 'rarity':
                valA = parseInt(a.dataset.rarityOrder) || 1;
                valB = parseInt(b.dataset.rarityOrder) || 1;
                return sortDir === 'asc' ? valA - valB : valB - valA;
            default:
                return 0;
        }
    });

    const productList = document.getElementById('product-list');
    itemsArray.forEach(item => productList.appendChild(item));
}
```

Add `const sortFilter = document.getElementById("sort-filter");` near line 625.
Call `sortItems()` inside `applyFilters()` after the visibility loop.
Add event listener: `sortFilter.addEventListener("change", () => { sortItems(); });`

Also add `sortFilter.value = "name-asc";` to the reset button handler.

### Step-by-Step
1. Open `index.php`
2. Add `$rarityOrderMap` and `$rarityOrder` PHP logic before the item-row div
3. Add `data-price`, `data-rating`, `data-rarity-order` attributes to the item-row div
4. Add the sort dropdown HTML in the sidebar form after the type filter
5. Add `const sortFilter` declaration in the JS
6. Add the `sortItems()` function in the JS
7. Call `sortItems()` at the end of `applyFilters()`
8. Add `sortFilter` change event listener
9. Add `sortFilter.value = "name-asc"` to reset handler
10. Test: select different sort options and verify items reorder

---

## F4 - Rarity + Price Filters on Catalog

### Spec
Add rarity filter dropdown and price range filter to the sidebar.

### Files to Modify
- `index.php` — sidebar filter section

### Changes

#### Add rarity filter dropdown (after the sort dropdown from F3)
```html
<div class="filter-group" style="margin-top:15px;">
    <label>Rarrete</label>
    <select id="rarity-filter" class="filter-select">
        <option value="all">Toutes les rarretes</option>
        <option value="rarity-commun">Commun</option>
        <option value="rarity-rare">Rare</option>
        <option value="rarity-epique">Epique</option>
        <option value="rarity-legendaire">Legendaire</option>
        <option value="rarity-mythique">Mythique</option>
    </select>
</div>
```

#### Add price range filter (after rarity)
```html
<div class="filter-group" style="margin-top:15px;">
    <label>Prix max (or)</label>
    <input type="number" id="price-filter" class="filter-input" placeholder="Ex: 50" min="0">
</div>
```

#### Update JS applyFilters()
Add these to the visibility check inside the `items.forEach` loop:

```javascript
const rarityFilter = document.getElementById("rarity-filter");
const priceFilter = document.getElementById("price-filter");

// Inside applyFilters(), add these checks:
const selectedRarity = rarityFilter.value;
const maxPrice = priceFilter.value !== '' ? parseFloat(priceFilter.value) : null;

const matchesRarity = (selectedRarity === "all" || item.dataset.rarity === selectedRarity);
const itemPrice = parseFloat(item.dataset.price) || 0;
const matchesPrice = (maxPrice === null || itemPrice <= maxPrice);

// Change the visibility condition from:
// if (matchesType && matchesSearch)
// to:
if (matchesType && matchesSearch && matchesRarity && matchesPrice)
```

Add event listeners:
```javascript
rarityFilter.addEventListener("change", applyFilters);
priceFilter.addEventListener("input", applyFilters);
```

Add to reset handler:
```javascript
rarityFilter.value = "all";
priceFilter.value = "";
```

### Step-by-Step
1. Open `index.php`
2. Add rarity and price filter HTML in sidebar after the sort dropdown
3. Add `rarityFilter` and `priceFilter` JS variables
4. Add `matchesRarity` and `matchesPrice` checks in `applyFilters()`
5. Update the visibility condition to include both new checks
6. Add event listeners for both new filters
7. Add reset logic for both new filters
8. Test: filter by rarity "Rare" → only rare items shown; set max price 20 → only items with price ≤ 20 shown

---

## F5 - Mage Check at Checkout (confirmer_achat.php)

### Spec
Non-mage players must not be able to purchase MagicSpell items. Currently checked at add-to-cart only; needs re-check at checkout.

### Files to Modify
- `backend/confirmer_achat.php` — after the cart items fetch (line 83), before balance check

### Changes

Add after line 83 (`$cartItems = $cartItemsStmt->fetchAll();`) and the empty check:

```php
$userRole = $userRow['role'] ?? 'Player';
$isMage = ($userRole === 'Mage');

if (!$isMage) {
    $spellTypeStmt = $pdo->prepare(
        "SELECT 1 FROM CartItems ci JOIN Items i ON i.ItemId = ci.ItemId JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId WHERE ci.CartId = :cartId AND t.Name = 'MagicSpell' LIMIT 1"
    );
    $spellTypeStmt->execute([':cartId' => $cartId]);

    if ($spellTypeStmt->fetchColumn()) {
        fail_checkout($pdo, 'Seuls les mages peuvent acheter des sorts.', 'spell_restricted');
    }
}
```

Also need to add `Role` to the user SELECT at line 41-46:

Change:
```sql
SELECT UserId, Gold, Silver, Bronze FROM Users WHERE UserId = :userId FOR UPDATE
```

To:
```sql
SELECT UserId, Gold, Silver, Bronze, Role FROM Users WHERE UserId = :userId FOR UPDATE
```

### Step-by-Step
1. Open `backend/confirmer_achat.php`
2. Add `Role` to the user SELECT query (line 42)
3. After line 87 (the empty cartItems check), add the mage check block
4. Test: as a non-mage, add a spell to cart, then checkout → should get "spell_restricted" error

---

## F6 - Comment Field on Reviews

### Spec
Players can leave a text comment alongside their star rating.

### Files to Modify
- `backend/soumettre_review.php` — lines 58-155 (read comment from POST, use in INSERT)
- `inventory.php` — lines 346-374 (add comment textarea to review form)
- `details.php` — add review section with comment (currently no review form on details page, reviews only shown from DB)

### Changes

#### soumettre_review.php
After line 59 (`$ratingRaw = ...`), add:
```php
$comment = trim((string)($_POST['comment'] ?? ''));
if (mb_strlen($comment) > 500) {
    review_response([
        'success' => false,
        'message' => 'Le commentaire ne doit pas depasser 500 caracteres.',
    ], '../inventory.php', $isAjax, 422);
}
$commentValue = ($comment !== '') ? $comment : null;
```

Change line 148-155 INSERT:
```php
$insertStmt = $pdo->prepare(
    "INSERT INTO Reviews (UserId, ItemId, Rating, Comment)
    VALUES (:user_id, :item_id, :rating, :comment)"
);
$insertStmt->execute([
    ':user_id' => $userId,
    ':item_id' => $itemId,
    ':rating' => $normalizedRating,
    ':comment' => $commentValue,
]);
```

#### inventory.php
In the pending-review-form (around line 347), add a comment textarea after the rating picker and before the submit button:

```html
<div class="pending-review-comment">
    <label for="comment-<?= $reviewItemId ?>">Commentaire (optionnel)</label>
    <textarea name="comment" id="comment-<?= $reviewItemId ?>" class="review-comment-input" maxlength="500" rows="2" placeholder="Partagez votre avis..."></textarea>
</div>
```

Add CSS in inventory.css:
```css
.pending-review-comment {
    margin-top: 10px;
}
.pending-review-comment label {
    display: block;
    font-size: 0.85rem;
    color: var(--accent);
    margin-bottom: 4px;
}
.review-comment-input {
    width: 100%;
    background: rgba(255, 255, 255, 0.05);
    border: 1px solid rgba(255, 255, 255, 0.1);
    border-radius: 4px;
    padding: 8px;
    color: white;
    font-size: 0.9rem;
    resize: vertical;
}
.review-comment-input:focus {
    border-color: var(--accent);
    outline: none;
}
```

### Step-by-Step
1. Open `backend/soumettre_review.php`
2. Add `$comment` variable extraction after `$ratingRaw` (line 59)
3. Add length validation (max 500)
4. Change INSERT to use `:comment` instead of `NULL`
5. Open `inventory.php`
6. Add comment textarea in the pending-review-form (before the submit button)
7. Add CSS for the comment input
8. Test: submit a review with a comment → check DB that Comment column is populated

---

## F7 - Player Deletes Own Review

### Spec
A player can delete their own review. Show a delete button next to their review.

### Files to Modify
- `backend/soumettre_review.php` — add DELETE handler (new action parameter)
- `details.php` — add delete button next to each review authored by current user
- `inventory.php` — add delete button on rated items

### Changes

#### soumettre_review.php — Add DELETE handler at the top (after session check, before the existing POST logic)
```php
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'delete_review') {
    $reviewId = (int)($_POST['review_id'] ?? 0);

    if ($reviewId <= 0) {
        review_response([
            'success' => false,
            'message' => 'Identifiant de review invalide.',
        ], '../inventory.php', $isAjax, 422);
    }

    $pdo = get_pdo();

    try {
        $checkStmt = $pdo->prepare("SELECT ReviewId, ItemId FROM Reviews WHERE ReviewId = :rid AND UserId = :uid");
        $checkStmt->execute([':rid' => $reviewId, ':uid' => $userId]);
        $reviewRow = $checkStmt->fetch();

        if (!$reviewRow) {
            review_response([
                'success' => false,
                'message' => 'Review introuvable ou vous n\'etes pas l\'auteur.',
            ], '../inventory.php', $isAjax, 403);
        }

        $deletedItemId = (int)$reviewRow['itemid'];

        $pdo->prepare("DELETE FROM Reviews WHERE ReviewId = :rid AND UserId = :uid")->execute([':rid' => $reviewId, ':uid' => $userId]);

        review_response([
            'success' => true,
            'message' => 'Votre evaluation a ete supprimee.',
            'deleted_item_id' => $deletedItemId,
        ], '../inventory.php', $isAjax);
    } catch (Throwable $e) {
        review_response([
            'success' => false,
            'message' => 'Erreur lors de la suppression.',
        ], '../inventory.php', $isAjax, 500);
    }
}
```

#### details.php — Add review list with delete buttons
Currently `details.php` has no review list section. Add one after the purchase section (around line 236, before `</main>`):

```php
<?php
$reviewsStmt = $pdo->prepare("
    SELECT r.ReviewId, r.UserId, r.Rating, r.Comment, r.CreatedAt, u.Alias
    FROM Reviews r
    JOIN Users u ON u.UserId = r.UserId
    WHERE r.ItemId = ?
    ORDER BY r.CreatedAt DESC
");
$reviewsStmt->execute([$itemId]);
$reviews = $reviewsStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="reviews-section" style="margin-top: 30px;">
    <h3><i class="fa-solid fa-comments"></i> Evaluations (<?= count($reviews) ?>)</h3>

    <?php if (empty($reviews)): ?>
        <p style="color: var(--text-silver);">Aucune evaluation pour le moment.</p>
    <?php else: ?>
        <?php foreach ($reviews as $rev): ?>
            <div class="review-card" data-review-id="<?= (int)$rev['reviewid'] ?>">
                <div class="review-header">
                    <strong><?= htmlspecialchars($rev['alias']) ?></strong>
                    <?= renderRatingStars((float)$rev['rating']) ?>
                    <span class="rating-value-inline"><?= formatRatingValue((float)$rev['rating']) ?>/5</span>
                    <?php if ($user['isConnected'] && (int)$rev['userid'] === $user['id']): ?>
                        <button type="button" class="btn-delete-review" data-review-id="<?= (int)$rev['reviewid'] ?>" title="Supprimer mon evaluation">
                            <i class="fa-solid fa-trash"></i>
                        </button>
                    <?php endif; ?>
                </div>
                <?php if (!empty($rev['comment'])): ?>
                    <p class="review-comment"><?= nl2br(htmlspecialchars($rev['comment'])) ?></p>
                <?php endif; ?>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>
```

Add JS for delete:
```javascript
document.querySelectorAll('.btn-delete-review').forEach(btn => {
    btn.addEventListener('click', async function() {
        if (!confirm('Supprimer votre evaluation ?')) return;
        const reviewId = this.dataset.reviewId;
        const formData = new FormData();
        formData.append('action', 'delete_review');
        formData.append('review_id', reviewId);
        try {
            const resp = await fetch('backend/soumettre_review.php', {
                method: 'POST',
                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                body: formData
            });
            const data = await resp.json();
            if (data.success) {
                const card = btn.closest('.review-card');
                if (card) card.remove();
                showToast(data.message, 'succes');
            } else {
                showToast(data.message || 'Erreur lors de la suppression.', 'erreur');
            }
        } catch (e) {
            showToast('Erreur reseau.', 'erreur');
        }
    });
});
```

Add basic CSS for review-card:
```css
.review-card {
    background: rgba(255,255,255,0.04);
    border: 1px solid rgba(255,255,255,0.08);
    border-radius: 8px;
    padding: 12px 16px;
    margin-bottom: 10px;
}
.review-header {
    display: flex;
    align-items: center;
    gap: 8px;
    flex-wrap: wrap;
}
.review-comment {
    margin-top: 8px;
    color: var(--text-light);
    font-size: 0.9rem;
}
.btn-delete-review {
    background: transparent;
    border: 1px solid #e74c3c;
    color: #e74c3c;
    border-radius: 4px;
    padding: 3px 8px;
    cursor: pointer;
    font-size: 0.75rem;
    margin-left: auto;
}
.btn-delete-review:hover {
    background: #e74c3c;
    color: white;
}
```

### Step-by-Step
1. Open `backend/soumettre_review.php`
2. Add the DELETE handler block after the session check (before existing review INSERT logic)
3. Open `details.php`
4. Add the reviews section HTML after the purchase section
5. Add the delete button JS
6. Add the review-card CSS
7. Test: submit a review as a user → see it on details page → click delete → review disappears

---

## F8 - Admin Deletes Player Review

### Spec
Admin can delete any player's review from the admin panel.

### Files to Modify
- `admin.php` — add new tab + review list + delete action

### Changes

#### admin.php — Add delete_review action (in the POST actions section, after line 159)
```php
// 11. Supprimer une evaluation
elseif ($_POST['action'] === 'delete_review') {
    $reviewId = (int)$_POST['review_id'];
    try {
        $pdo->prepare("DELETE FROM Reviews WHERE ReviewId = ?")->execute([$reviewId]);
        $message_alerte = ["type" => "succes", "texte" => "L'evaluation a ete supprimee."];
    } catch (Exception $e) {
        $message_alerte = ["type" => "erreur", "texte" => "Erreur lors de la suppression de l'evaluation."];
    }
}
```

#### admin.php — Add Reviews tab button (in the admin-menu, after line 336)
```html
<button type="button" class="admin-tab-btn" onclick="switchTab(event, 'reviews')"><i class="fa-solid fa-star"></i> Evaluations</button>
```

#### admin.php — Add reviews data fetch (after line 171, in the data retrieval section)
```php
$reviews = [];
try {
    $reviews = $pdo->query("SELECT r.ReviewId, r.Rating, r.Comment, r.CreatedAt, u.Alias AS ReviewerAlias, i.Name AS ItemName FROM Reviews r JOIN Users u ON u.UserId = r.UserId JOIN Items i ON i.ItemId = r.ItemId ORDER BY r.CreatedAt DESC LIMIT 200")->fetchAll();
} catch (Exception $e) {}
```

#### admin.php — Add reviews tab section (after the tab-requests div, around line 688)
```html
<div id="tab-reviews" class="admin-section details-glass-card">
    <h3><i class="fa-solid fa-star"></i> Gestion des Evaluations</h3>
    <div style="overflow-x:auto;">
        <table class="glass-table">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Joueur</th>
                    <th>Item</th>
                    <th>Note</th>
                    <th>Commentaire</th>
                    <th>Date</th>
                    <th>Actions</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($reviews)): ?>
                <tr><td colspan="7">Aucune evaluation.</td></tr>
                <?php else: ?>
                <?php foreach ($reviews as $rev): ?>
                <tr>
                    <td>#<?= (int)$rev['reviewid'] ?></td>
                    <td><strong><?= htmlspecialchars($rev['revieweralias']) ?></strong></td>
                    <td><?= htmlspecialchars($rev['itemname']) ?></td>
                    <td><?= formatRatingValue((float)$rev['rating']) ?>/5</td>
                    <td style="max-width:200px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= htmlspecialchars($rev['comment'] ?? '') ?></td>
                    <td><?= htmlspecialchars((string)$rev['createdat']) ?></td>
                    <td>
                        <form style="display:inline;" method="POST" action="admin.php" class="confirm-delete-review-form">
                            <input type="hidden" name="action" value="delete_review">
                            <input type="hidden" name="review_id" value="<?= (int)$rev['reviewid'] ?>">
                            <button type="submit" class="btn-danger" style="padding:5px;" title="Supprimer"><i class="fa-solid fa-trash"></i></button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
```

Add confirm JS for the new delete form (near line 784):
```javascript
document.querySelectorAll('.confirm-delete-review-form').forEach(function(form) {
    form.addEventListener('submit', async function(e) {
        e.preventDefault();
        var ok = await showCustomConfirm('Supprimer cette evaluation ?', 'Suppression');
        if (ok) form.submit();
    });
});
```

### Step-by-Step
1. Open `admin.php`
2. Add the `delete_review` POST action after line 159
3. Add the Reviews tab button in the admin menu
4. Add the `$reviews` data fetch query
5. Add the Reviews tab HTML section
6. Add the confirm-delete JS handler
7. Test: as admin, view reviews tab, delete a review → review removed from DB

---

## F9 - Star Distribution Percentage Display

### Spec
Show the percentage breakdown per star level (1-5) for an item's reviews, not just the average.

### Files to Modify
- `details.php` — in the stats-grid or reviews section, add star distribution bars

### Changes

#### details.php — Add star distribution query (after the item fetch, around line 39)
```php
$distributionStmt = $pdo->prepare("
    SELECT
        ROUND(Rating) AS star_level,
        COUNT(*) AS count
    FROM Reviews
    WHERE ItemId = ?
    GROUP BY ROUND(Rating)
    ORDER BY ROUND(Rating) DESC
");
$distributionStmt->execute([$itemId]);
$distRows = $distributionStmt->fetchAll(PDO::FETCH_ASSOC);
$starDist = [5 => 0, 4 => 0, 3 => 0, 2 => 0, 1 => 0];
$totalReviewsForDist = 0;
foreach ($distRows as $dr) {
    $level = (int)$dr['star_level'];
    if ($level >= 1 && $level <= 5) {
        $starDist[$level] = (int)$dr['count'];
        $totalReviewsForDist += (int)$dr['count'];
    }
}
```

#### details.php — Add distribution display (in the stats-grid, after the Avis stat-box, around line 163)
```html
<div class="star-distribution">
    <?php for ($s = 5; $s >= 1; $s--):
        $pct = $totalReviewsForDist > 0 ? round(($starDist[$s] / $totalReviewsForDist) * 100) : 0;
    ?>
    <div class="dist-row">
        <span class="dist-label"><?= $s ?> <i class="fa-solid fa-star"></i></span>
        <div class="dist-bar-bg"><div class="dist-bar-fill" style="width: <?= $pct ?>%"></div></div>
        <span class="dist-pct"><?= $pct ?>%</span>
    </div>
    <?php endfor; ?>
</div>
```

Add CSS:
```css
.star-distribution {
    margin-top: 12px;
    width: 100%;
}
.dist-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 4px;
}
.dist-label {
    width: 40px;
    text-align: right;
    font-size: 0.78rem;
    color: var(--text-silver);
}
.dist-bar-bg {
    flex: 1;
    height: 8px;
    background: rgba(255,255,255,0.08);
    border-radius: 4px;
    overflow: hidden;
}
.dist-bar-fill {
    height: 100%;
    background: #d9c176;
    border-radius: 4px;
    transition: width 0.3s ease;
}
.dist-pct {
    width: 36px;
    text-align: right;
    font-size: 0.75rem;
    color: var(--text-silver);
}
```

### Step-by-Step
1. Open `details.php`
2. Add the star distribution query after the item fetch
3. Add the distribution display HTML in the stats-grid
4. Add the CSS
5. Test: view an item with multiple reviews → see percentage bars per star level

---

## F10 - Admin Capital Increase (FundsGivenCount logic)

### Spec
When admin gives funds to a player, increment `FundsGivenCount`. If `FundsGivenCount` >= 3, prevent further increases (admin sees warning). The column already exists in DB (from sprint3_migrations.sql).

### Files to Modify
- `admin.php` — add_funds action (lines 132-137), add FundsGivenCount check and increment

### Changes

Replace the add_funds action (lines 132-137):
```php
elseif ($_POST['action'] === 'add_funds') {
    $targetUserId = (int)$_POST['user_id'];
    $addGold = (int)$_POST['add_gold']; $addSilver = (int)$_POST['add_silver']; $addBronze = (int)$_POST['add_bronze'];

    try {
        $pdo->beginTransaction();

        $fundsStmt = $pdo->prepare("SELECT FundsGivenCount FROM Users WHERE UserId = ? FOR UPDATE");
        $fundsStmt->execute([$targetUserId]);
        $fundsRow = $fundsStmt->fetch();

        if (!$fundsRow) {
            $pdo->rollBack();
            $message_alerte = ["type" => "erreur", "texte" => "Joueur introuvable."];
        } elseif ((int)$fundsRow['fundsgivencount'] >= 3) {
            $pdo->rollBack();
            $message_alerte = ["type" => "erreur", "texte" => "Ce joueur a deja recu 3 augmentations de capital. Limite atteinte."];
        } else {
            $pdo->prepare("UPDATE Users SET Gold = Gold + ?, Silver = Silver + ?, Bronze = Bronze + ?, FundsGivenCount = FundsGivenCount + 1 WHERE UserId = ?")->execute([$addGold, $addSilver, $addBronze, $targetUserId]);
            $pdo->commit();
            $message_alerte = ["type" => "succes", "texte" => "Les fonds du joueur ont ete mis a jour. (Augmentation " . ((int)$fundsRow['fundsgivencount'] + 1) . "/3)"];
        }
    } catch (Exception $e) {
        if ($pdo->inTransaction()) { $pdo->rollBack(); }
        $message_alerte = ["type" => "erreur", "texte" => "Erreur lors de l'ajout de fonds."];
    }
}
```

Also update the players query (line 177) to include `FundsGivenCount`:
```sql
SELECT u.UserId, u.Alias, u.Role, u.Gold, u.Silver, u.Bronze, u.FundsGivenCount, ...
```

Display FundsGivenCount in the players table (after the Capital column):
```html
<td><?= (int)($p['fundsgivencount'] ?? 0) ?>/3</td>
```

Add a column header:
```html
<th>Capital Donnes</th>
```

### Step-by-Step
1. Open `admin.php`
2. Replace the add_funds action block with the new version including FundsGivenCount logic
3. Add FundsGivenCount to the players SELECT query
4. Add FundsGivenCount column to the players table display
5. Test: give funds to a player 3 times → 4th attempt should fail with limit message

---

## F11 - Admin View Player Inventories

### Spec
Admin can view any player's inventory contents.

### Files to Modify
- `admin.php` — add inventory viewer in the users tab

### Changes

#### Add inventory viewing section in the users tab (after the players table, around line 629)
```html
<hr style="border-color: rgba(255,255,255,0.1); margin: 30px 0;">

<h3><i class="fa-solid fa-box-open"></i> Inventaire d'un Joueur</h3>
<div class="admin-form-group" style="margin-bottom:15px;">
    <label>Selectionner un joueur pour voir son inventaire</label>
    <select id="inventory-player-select" class="admin-input">
        <option value="" disabled selected>-- Choisir un joueur --</option>
        <?php foreach ($players as $p): ?>
        <option value="<?= (int)$p['userid'] ?>"><?= htmlspecialchars($p['alias']) ?></option>
        <?php endforeach; ?>
    </select>
</div>
<div id="admin-inventory-result" style="overflow-x:auto;"></div>
```

#### Add JS for AJAX inventory fetch
```javascript
const invSelect = document.getElementById('inventory-player-select');
const invResult = document.getElementById('admin-inventory-result');

if (invSelect && invResult) {
    invSelect.addEventListener('change', async function() {
        const userId = this.value;
        if (!userId) {
            invResult.innerHTML = '';
            return;
        }
        try {
            const resp = await fetch('backend/admin_get_inventory.php?user_id=' + encodeURIComponent(userId));
            const data = await resp.json();
            if (data.success && data.items.length > 0) {
                let html = '<table class="glass-table"><thead><tr><th>Item</th><th>Type</th><th>Quantite</th><th>Prix (G/S/B)</th></tr></thead><tbody>';
                data.items.forEach(function(item) {
                    html += '<tr><td>' + (item.name || 'Inconnu') + '</td><td>' + (item.type || '-') + '</td><td>' + (item.quantity || 0) + '</td><td>' + (item.gold || 0) + '/' + (item.silver || 0) + '/' + (item.bronze || 0) + '</td></tr>';
                });
                html += '</tbody></table>';
                invResult.innerHTML = html;
            } else {
                invResult.innerHTML = '<p style="color:var(--text-silver);">Aucun item dans l\'inventaire de ce joueur.</p>';
            }
        } catch (e) {
            invResult.innerHTML = '<p style="color:#e74c3c;">Erreur lors du chargement.</p>';
        }
    });
}
```

#### Create new file: `backend/admin_get_inventory.php`
```php
<?php
require_once __DIR__ . '/../includes/session.php';
require_once __DIR__ . '/../AlgosBD.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user']) || $_SESSION['user']['role'] !== 'Admin') {
    echo json_encode(['success' => false, 'message' => 'Acces refuse.']);
    exit;
}

$userId = (int)($_GET['user_id'] ?? 0);
if ($userId <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalide.']);
    exit;
}

$pdo = get_pdo();

try {
    $stmt = $pdo->prepare("
        SELECT i.Name AS name, t.Name AS type, inv.Quantity AS quantity,
               i.PriceGold AS gold, i.PriceSilver AS silver, i.PriceBronze AS bronze
        FROM Inventory inv
        JOIN Items i ON i.ItemId = inv.ItemId
        JOIN ItemTypes t ON i.ItemTypeId = t.ItemTypeId
        WHERE inv.UserId = :uid
        ORDER BY i.Name ASC
    ");
    $stmt->execute([':uid' => $userId]);
    $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'items' => $items]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Erreur serveur.']);
}
```

### Step-by-Step
1. Create `backend/admin_get_inventory.php` with the above content
2. Open `admin.php`
3. Add the inventory viewer HTML in the users tab
4. Add the JS for AJAX fetch
5. Test: as admin, select a player in the dropdown → see their inventory items

---

## F12 - Inventory Filtering (optional)

### Spec
Add optional filter controls on `inventory.php` to filter by item type and search by name.

### Files to Modify
- `inventory.php` — add filter bar above the inventory grid

### Changes

#### Add filter bar (after the catalog-banner div, around line 177)
```html
<div class="inventory-filter-bar" style="display:flex; gap:12px; margin-bottom:16px; flex-wrap:wrap; align-items:center;">
    <input type="text" id="inv-search-filter" class="filter-input" placeholder="Rechercher un item..." style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); padding:8px; color:white; border-radius:4px;">
    <select id="inv-type-filter" class="filter-select" style="background:rgba(255,255,255,0.05); border:1px solid rgba(255,255,255,0.1); padding:8px; color:white; border-radius:4px;">
        <option value="all">Tous les types</option>
        <option value="weapon">Armes</option>
        <option value="armor">Armures</option>
        <option value="potion">Potions</option>
        <option value="magicspell">Sorts</option>
    </select>
    <button type="button" id="inv-reset-filters" style="background:transparent; border:1px solid var(--accent); color:var(--accent); padding:8px 14px; cursor:pointer; border-radius:4px; font-weight:bold;">Reinitialiser</button>
</div>
```

#### Add JS filtering logic
```javascript
const invSearchFilter = document.getElementById('inv-search-filter');
const invTypeFilter = document.getElementById('inv-type-filter');
const invResetBtn = document.getElementById('inv-reset-filters');
const invSlots = document.querySelectorAll('.inventory-slot');

function applyInventoryFilters() {
    const searchVal = (invSearchFilter.value || '').toLowerCase().trim();
    const typeVal = invTypeFilter.value;
    let visibleCount = 0;

    invSlots.forEach(slot => {
        const name = (slot.dataset.itemName || '').toLowerCase();
        const type = (slot.dataset.itemType || '').toLowerCase();
        const normalizedName = name.normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        const normalizedSearch = searchVal.normalize('NFD').replace(/[\u0300-\u036f]/g, '');

        const matchesSearch = !searchVal || normalizedName.includes(normalizedSearch) || name.includes(searchVal);
        const matchesType = typeVal === 'all' || type === typeVal;

        slot.style.display = (matchesSearch && matchesType) ? '' : 'none';
        if (matchesSearch && matchesType) visibleCount++;
    });
}

invSearchFilter.addEventListener('input', applyInventoryFilters);
invTypeFilter.addEventListener('change', applyInventoryFilters);
invResetBtn.addEventListener('click', () => {
    invSearchFilter.value = '';
    invTypeFilter.value = 'all';
    applyInventoryFilters();
});
```

### Step-by-Step
1. Open `inventory.php`
2. Add the filter bar HTML after the banner
3. Add the JS filtering logic (in the existing DOMContentLoaded listener or a new script block)
4. Test: type a search term → items filter; select a type → items filter; reset → all shown

---

## F13 - Smart Potion/Spell Use (prevent waste)

### Spec
If using a potion/spell would heal more HP than needed (overheal), warn the player. The item is still consumed, but the player is informed of the waste.

Actually, per the spec intent: **prevent** the waste. If `CurrentHP + healAmount > MaxHP` and the player would waste more than half the heal, show a warning and let them confirm or cancel.

### Files to Modify
- `backend/use_item.php` — no changes needed (already clamps to MaxHP)
- `inventory.php` — add client-side warning before use

### Changes

#### inventory.php — Modify the "Utiliser" button click handler
The use-item logic is in `assets/js/inventory.js`. Add a pre-check:

In `inventory.js`, before the fetch call to `use_item.php`, add:
```javascript
// Smart use check: warn if overheal would waste more than half the heal value
// We need current HP and heal amount. We can get current HP from the header display.
// For simplicity, we'll add data attributes to the use button.
```

Better approach: add `data-heal-amount` to the use button in `inventory.php` (line 274):
```html
<button type="button" class="btn-use-item" data-item-id="<?= (int)$entry['item_id'] ?>" data-item-name="<?= htmlspecialchars($entry['item_name'], ENT_QUOTES, 'UTF-8') ?>" data-item-type="<?= htmlspecialchars($entry['item_type'], ENT_QUOTES, 'UTF-8') ?>">
```

Then in `inventory.js`, before the fetch to `use_item.php`, add:
```javascript
const currentHP = <?= (int)($_SESSION['user']['hp'] ?? 100) ?>;
const maxHP = <?= (int)($_SESSION['user']['max_hp'] ?? 100) ?>;

// Before sending the use_item request:
if (currentHP >= maxHP) {
    showToast('Vos PV sont deja au maximum !', 'erreur');
    return;
}
```

Actually, the server already handles this (line 73-77 of use_item.php returns error if HP is max). The "smart use" feature is about **warning before consuming** if the heal would be partially wasted.

Add this check in `inventory.js` before the fetch:
```javascript
// Get heal amount from server response or estimate it
// Since we don't know exact heal amount client-side, we'll add a data attribute
```

Best approach: Add a PHP-computed `data-heal-value` attribute to the use button.

In `inventory.php`, inside the potion/magicspell conditional (around line 273-277):
```php
<?php if (strtolower($entry['item_type']) === 'potion' || strtolower($entry['item_type']) === 'magicspell'):
    $healValue = 3; // default
    if (strtolower($entry['item_type']) === 'potion') {
        $propHealStmt = $pdo->prepare("SELECT EffectValue FROM PotionProperties WHERE ItemId = ?");
        $propHealStmt->execute([(int)$entry['item_id']]);
        $propHealRow = $propHealStmt->fetch();
        if ($propHealRow) $healValue = min((int)$propHealRow['effectvalue'], 5);
    } elseif (strtolower($entry['item_type']) === 'magicspell') {
        $propHealStmt = $pdo->prepare("SELECT SpellDamage FROM MagicSpellProperties WHERE ItemId = ?");
        $propHealStmt->execute([(int)$entry['item_id']]);
        $propHealRow = $propHealStmt->fetch();
        if ($propHealRow) $healValue = max((int)floor((int)$propHealRow['spelldamage'] / 2), 3);
    }
    $currentHP = (int)($_SESSION['user']['hp'] ?? 100);
    $maxHP = (int)($_SESSION['user']['max_hp'] ?? 100);
    $effectiveHeal = min($healValue, $maxHP - $currentHP);
    $wouldWaste = ($healValue > $effectiveHeal);
?>
<button type="button" class="btn-use-item" data-item-id="<?= (int)$entry['item_id'] ?>" data-item-name="<?= htmlspecialchars($entry['item_name'], ENT_QUOTES, 'UTF-8') ?>" data-heal-value="<?= $healValue ?>" data-would-waste="<?= $wouldWaste ? '1' : '0' ?>">
    <i class="fa-solid fa-hand-sparkles"></i> Utiliser
</button>
<?php endif; ?>
```

Then in `inventory.js`, add confirmation before use:
```javascript
// In the use-item click handler, before fetch:
const wouldWaste = button.dataset.wouldWaste === '1';
if (wouldWaste) {
    const confirmed = await showCustomConfirm(
        'Cet objet va partiellement gaspiller son effet (PV presque au max). Continuer ?',
        'Gaspillage potentiel'
    );
    if (!confirmed) return;
}
```

### Step-by-Step
1. Open `inventory.php`
2. Add the heal-value calculation and data attributes to the use button
3. Open `assets/js/inventory.js`
4. Add the waste confirmation check before the fetch call
5. Test: with HP at 98/100, try using a potion that heals 5 HP → get warning about waste

---

## F14 - Enigma TrueFalse Type

### Spec
When a riddle has `RiddleType = 'TrueFalse'`, display 2 buttons (Vrai / Faux) instead of 4 choice buttons. Validate against `AnswerText` (which should be "Vrai" or "Faux").

### Files to Modify
- `includes/enigmes_request.php` — `generate_and_store_choices()` (lines 85-118) and `resolve_enigme_request()` (lines 137-223)
- `AlgosBD.php` — `get_active_riddle_by_id()` and `get_random_active_riddle()` — add `RiddleType` to SELECT
- `reponse.php` — add TrueFalse handling in POST (lines 52-151) and in the form display (lines 227-234)
- `enigme.php` — update mage dialogue for TrueFalse riddles
- `admin.php` — add RiddleType field to the add_riddle form

### Changes

#### AlgosBD.php — Add RiddleType to riddle queries

In `get_active_riddle_by_id()` (line 293-305), add to SELECT:
```sql
r.RiddleType AS riddle_type,
```

In `get_random_active_riddle()` (line 328-340), add to SELECT:
```sql
r.RiddleType AS riddle_type,
```

#### includes/enigmes_request.php — Update generate_and_store_choices()

Replace the function (lines 85-118):
```php
function generate_and_store_choices(array $riddle): array
{
    $riddleType = $riddle['riddle_type'] ?? 'MultipleChoice';
    $answerText = get_riddle_answer_text((int) $riddle['id']);

    if ($answerText === null) {
        return [];
    }

    if ($riddleType === 'TrueFalse') {
        $_SESSION['enigme_choices_' . $riddle['id']] = [
            'correct_index' => (strtolower(trim($answerText)) === 'vrai') ? 0 : 1,
            'choice_texts' => ['Vrai', 'Faux'],
            'riddle_type' => 'TrueFalse',
        ];
        return ['Vrai', 'Faux'];
    }

    if ($riddleType === 'ShortAnswer') {
        $_SESSION['enigme_choices_' . $riddle['id']] = [
            'correct_text' => $answerText,
            'riddle_type' => 'ShortAnswer',
        ];
        return [];
    }

    // MultipleChoice (default)
    $choices = [
        $answerText,
        $riddle['wrong_answer1'] ?? '',
        $riddle['wrong_answer2'] ?? '',
        $riddle['wrong_answer3'] ?? '',
    ];

    $correctIndex = 0;
    $keys = array_keys($choices);
    shuffle($keys);
    $shuffled = [];

    foreach ($keys as $newIndex => $oldIndex) {
        $shuffled[$newIndex] = $choices[$oldIndex];
        if ($oldIndex === 0) {
            $correctIndex = $newIndex;
        }
    }

    $_SESSION['enigme_choices_' . $riddle['id']] = [
        'correct_index' => $correctIndex,
        'choice_texts' => $shuffled,
        'riddle_type' => 'MultipleChoice',
    ];

    return $shuffled;
}
```

#### includes/enigmes_request.php — Update verify_enigme_choice()

Replace (lines 120-135):
```php
function verify_enigme_choice(int $riddleId, int $choiceIndex): array
{
    $key = 'enigme_choices_' . $riddleId;
    $data = $_SESSION[$key] ?? null;

    if (!is_array($data) || !isset($data['correct_index'], $data['choice_texts'])) {
        return ['is_correct' => false, 'chosen_text' => ''];
    }

    $isCorrect = $choiceIndex === (int) $data['correct_index'];
    $chosenText = $data['choice_texts'][$choiceIndex] ?? '';

    unset($_SESSION[$key]);

    return ['is_correct' => $isCorrect, 'chosen_text' => $chosenText];
}

function verify_enigme_short_answer(int $riddleId, string $userAnswer): array
{
    $key = 'enigme_choices_' . $riddleId;
    $data = $_SESSION[$key] ?? null;

    if (!is_array($data) || ($data['riddle_type'] ?? '') !== 'ShortAnswer') {
        return ['is_correct' => false, 'chosen_text' => $userAnswer];
    }

    $correctText = trim((string)($data['correct_text'] ?? ''));
    $normalizedUser = mb_strtolower(trim($userAnswer), 'UTF-8');
    $normalizedCorrect = mb_strtolower($correctText, 'UTF-8');

    $isCorrect = ($normalizedUser === $normalizedCorrect);
    unset($_SESSION[$key]);

    return ['is_correct' => $isCorrect, 'chosen_text' => $userAnswer];
}
```

#### reponse.php — Update POST handler and form

In the POST section (around line 52), add ShortAnswer handling before the existing choice_index logic:

```php
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $riddleType = $context['riddle']['riddle_type'] ?? 'MultipleChoice';

    if ($riddleType === 'ShortAnswer') {
        $userAnswer = trim((string)($_POST['short_answer'] ?? ''));

        if ($userAnswer === '') {
            set_enigmes_flash_dialogues([
                ['text' => 'Tu dois ecrire une reponse !', 'frame' => 'assets/img/Magicien/furieux.png'],
                ['text' => 'Allez, je te renvoie.', 'frame' => 'assets/img/Magicien/mage8.png'],
            ]);
            header('Location: ' . build_enigmes_page_url('enigme.php', $context['query']));
            exit;
        }

        $result = verify_enigme_short_answer((int)$context['riddle']['id'], $userAnswer);
        // ... then same correct/incorrect logic as below ...
    } else {
        // Existing choice_index logic for MultipleChoice and TrueFalse
        $choiceIndex = filter_input(INPUT_POST, 'choice_index', FILTER_VALIDATE_INT);
        $maxChoices = ($riddleType === 'TrueFalse') ? 1 : 3;

        if ($choiceIndex === false || $choiceIndex === null || $choiceIndex < 0 || $choiceIndex > $maxChoices) {
            // ... error handling ...
        }

        $result = verify_enigme_choice((int)$context['riddle']['id'], $choiceIndex);
        // ... same correct/incorrect logic ...
    }
}
```

**Note:** The correct/incorrect handling after `$result` is the same for all types. Refactor the shared logic into a helper, or duplicate it. For simplicity, use a shared block.

#### reponse.php — Update form display

Replace the choices section (lines 227-235):
```html
<div class="enigmes-orb" id="enigmesRiddleArea">
    <?php
    $riddleType = $context['riddle']['riddle_type'] ?? 'MultipleChoice';
    ?>

    <?php if ($riddleType === 'ShortAnswer'): ?>
    <form class="enigmes-form" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <div class="enigmes-short-answer">
            <input type="text" name="short_answer" class="enigmes-short-input" placeholder="Votre reponse..." autocomplete="off" required>
            <button type="submit" class="enigmes-choice-btn">Valider</button>
        </div>
    </form>

    <?php elseif ($riddleType === 'TrueFalse'): ?>
    <form class="enigmes-form" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <div class="enigmes-choices enigmes-choices-tf" id="enigmesChoices">
            <button type="submit" name="choice_index" value="0" class="enigmes-choice-btn enigmes-tf-btn">Vrai</button>
            <button type="submit" name="choice_index" value="1" class="enigmes-choice-btn enigmes-tf-btn">Faux</button>
        </div>
    </form>

    <?php else: ?>
    <form class="enigmes-form" action="<?= htmlspecialchars($formAction, ENT_QUOTES, 'UTF-8') ?>" method="post">
        <div class="enigmes-choices" id="enigmesChoices">
            <?php foreach ($context['choices'] as $i => $choice): ?>
            <button type="submit" name="choice_index" value="<?= $i ?>" class="enigmes-choice-btn"><?= htmlspecialchars($choice, ENT_QUOTES, 'UTF-8') ?></button>
            <?php endforeach; ?>
        </div>
    </form>
    <?php endif; ?>
</div>
```

Add CSS for TrueFalse and ShortAnswer:
```css
.enigmes-choices-tf {
    flex-direction: row !important;
    gap: 20px !important;
}
.enigmes-tf-btn {
    min-width: 120px !important;
    font-size: 1.2rem !important;
}
.enigmes-short-answer {
    display: flex;
    flex-direction: column;
    align-items: center;
    gap: 15px;
}
.enigmes-short-input {
    background: rgba(255,255,255,0.08);
    border: 1px solid rgba(255,255,255,0.2);
    border-radius: 8px;
    padding: 12px 20px;
    color: white;
    font-size: 1.1rem;
    width: 80%;
    max-width: 400px;
    text-align: center;
}
.enigmes-short-input:focus {
    border-color: var(--accent);
    outline: none;
    box-shadow: 0 0 12px rgba(25, 133, 161, 0.4);
}
```

#### enigme.php — Update mage dialogue

In `enigme.php` (line 25), change the dialogue to be type-aware:
```php
$riddleType = $context['riddle']['riddle_type'] ?? 'MultipleChoice';
$typeDialogue = match($riddleType) {
    'TrueFalse' => 'Deux choix s\'offriront a toi : Vrai ou Faux. Choisis avec conviction.',
    'ShortAnswer' => 'Tu devras ecrire ta reponse. Sois precis, jeune vagabond.',
    default => 'Quatre choix de reponses te seront presentes. Une seule est la bonne. Choisis avec sagesse.',
};
```

Replace the hardcoded line 25 with `$typeDialogue`.

#### admin.php — Add RiddleType to add_riddle form

In the add_riddle form (around line 437-497), add a RiddleType dropdown:
```html
<div class="admin-form-group">
    <label>Type d'enigme</label>
    <select name="riddle_type" class="admin-input" required>
        <option value="MultipleChoice">Choix multiples</option>
        <option value="TrueFalse">Vrai / Faux</option>
        <option value="ShortAnswer">Phrase courte</option>
    </select>
</div>
```

Update the add_riddle INSERT (line 101) to include `RiddleType`:
```php
$stmt = $pdo->prepare("INSERT INTO Riddles (QuestionText, AnswerText, WrongAnswer1, WrongAnswer2, WrongAnswer3, HintText, Difficulty, RiddleCategoryId, RewardGold, RewardSilver, RewardBronze, RiddleType, IsActive) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1)");
$stmt->execute([$question, $answer, $wrongAnswer1, $wrongAnswer2, $wrongAnswer3, $hint, $difficulty, $categoryId, $rewardGold, $rewardSilver, $rewardBronze, $_POST['riddle_type']]);
```

For TrueFalse and ShortAnswer, the wrong answer fields should be optional. Add conditional validation:
```php
$riddleType = $_POST['riddle_type'] ?? 'MultipleChoice';
if ($riddleType === 'TrueFalse') {
    $wrongAnswer1 = ($answer === 'Vrai') ? 'Faux' : 'Vrai';
    $wrongAnswer2 = '';
    $wrongAnswer3 = '';
} elseif ($riddleType === 'ShortAnswer') {
    $wrongAnswer1 = '';
    $wrongAnswer2 = '';
    $wrongAnswer3 = '';
}
```

Also make the wrong answer fields in the HTML form conditionally required via JS:
```javascript
const riddleTypeSelect = document.querySelector('select[name="riddle_type"]');
if (riddleTypeSelect) {
    riddleTypeSelect.addEventListener('change', function() {
        const isMC = this.value === 'MultipleChoice';
        document.querySelectorAll('input[name^="wrong_answer"]').forEach(input => {
            input.required = isMC;
            input.disabled = !isMC;
        });
    });
}
```

### Step-by-Step
1. Open `AlgosBD.php`
2. Add `r.RiddleType AS riddle_type` to both riddle SELECT queries
3. Open `includes/enigmes_request.php`
4. Replace `generate_and_store_choices()` with the type-aware version
5. Replace `verify_enigme_choice()` with the updated version + add `verify_enigme_short_answer()`
6. Open `reponse.php`
7. Update the POST handler with type branching
8. Update the form display with type-conditional HTML
9. Add CSS for TrueFalse and ShortAnswer
10. Open `enigme.php`
11. Make the mage dialogue type-aware
12. Open `admin.php`
13. Add RiddleType dropdown to add_riddle form
14. Update the INSERT to include RiddleType
15. Add conditional validation for wrong answer fields
16. Test: create a TrueFalse riddle → play it → see Vrai/Faux buttons; create a ShortAnswer riddle → play it → see text input

---

## F15 - Enigma ShortAnswer Type

> This is fully covered in F14 above. The ShortAnswer type is implemented alongside TrueFalse.

---

## F16 - Statistics as Graph/Chart

### Spec
Display player riddle statistics as a visual chart (bar or pie) instead of just numbers.

### Files to Modify
- `profile.php` — stats section (lines 176-202)

### Changes

Use a lightweight JS chart library. **Chart.js** (via CDN) is the simplest option.

#### profile.php — Add Chart.js CDN (in the head or before the stats section)
```html
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
```

#### profile.php — Replace the stats-grid with a chart
Replace lines 176-202 with:
```html
<section class="profile-stats-section">
    <h2><i class="fa-solid fa-chart-bar"></i> Statistiques d'enigmes</h2>
    <div class="stats-chart-container">
        <canvas id="riddleStatsChart" width="300" height="200"></canvas>
    </div>
    <div class="stats-grid" style="margin-top:15px;">
        <div class="stat-card">
            <span class="stat-label">Facile</span>
            <span class="stat-value"><?= $riddleStats['facile_solved'] ?>/<?= $riddleStats['facile_total'] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Moyenne</span>
            <span class="stat-value"><?= $riddleStats['moyenne_solved'] ?>/<?= $riddleStats['moyenne_total'] ?></span>
        </div>
        <div class="stat-card">
            <span class="stat-label">Difficile</span>
            <span class="stat-value"><?= $riddleStats['difficile_solved'] ?>/<?= $riddleStats['difficile_total'] ?></span>
        </div>
        <div class="stat-card stat-total">
            <span class="stat-label">Total</span>
            <span class="stat-value"><?= $riddleStats['solved_count'] ?>/<?= $riddleStats['facile_total'] + $riddleStats['moyenne_total'] + $riddleStats['difficile_total'] ?></span>
        </div>
        <?php if ($user['isMage']): ?>
        <div class="stat-card stat-mage">
            <span class="stat-label">Statut</span>
            <span class="stat-value"><i class="fa-solid fa-hat-wizard"></i> Mage</span>
        </div>
        <?php endif; ?>
    </div>
</section>
```

Add JS after the chart container:
```javascript
const ctx = document.getElementById('riddleStatsChart');
if (ctx) {
    new Chart(ctx, {
        type: 'bar',
        data: {
            labels: ['Facile', 'Moyenne', 'Difficile'],
            datasets: [
                {
                    label: 'Resolues',
                    data: [
                        <?= (int)$riddleStats['facile_solved'] ?>,
                        <?= (int)$riddleStats['moyenne_solved'] ?>,
                        <?= (int)$riddleStats['difficile_solved'] ?>
                    ],
                    backgroundColor: 'rgba(46, 204, 113, 0.7)',
                    borderColor: 'rgba(46, 204, 113, 1)',
                    borderWidth: 1
                },
                {
                    label: 'Total',
                    data: [
                        <?= (int)$riddleStats['facile_total'] ?>,
                        <?= (int)$riddleStats['moyenne_total'] ?>,
                        <?= (int)$riddleStats['difficile_total'] ?>
                    ],
                    backgroundColor: 'rgba(52, 152, 219, 0.4)',
                    borderColor: 'rgba(52, 152, 219, 1)',
                    borderWidth: 1
                }
            ]
        },
        options: {
            responsive: true,
            plugins: {
                legend: { labels: { color: '#ccc' } }
            },
            scales: {
                x: { ticks: { color: '#ccc' }, grid: { color: 'rgba(255,255,255,0.06)' } },
                y: { ticks: { color: '#ccc', stepSize: 1 }, grid: { color: 'rgba(255,255,255,0.06)' }, beginAtZero: true }
            }
        }
    });
}
```

Add CSS:
```css
.stats-chart-container {
    max-width: 400px;
    margin: 0 auto;
    background: rgba(255,255,255,0.03);
    border-radius: 8px;
    padding: 16px;
}
```

### Step-by-Step
1. Open `profile.php`
2. Add Chart.js CDN script tag
3. Add the chart canvas in the stats section
4. Add the Chart.js initialization script
5. Add the CSS for the chart container
6. Test: visit profile → see bar chart with resolved/total per difficulty

---

## F17 - MaxHP UI in Profile

### Spec
Allow players to see their MaxHP and (if the spec allows) modify it. Since the spec says "Fixer max PV" and MaxHP is already respected, we need at minimum a **display** of MaxHP with the ability for the admin to set it. The player profile should show HP as a bar.

### Files to Modify
- `profile.php` — add HP display section

### Changes

#### profile.php — Add HP section (after the stats section, around line 203)
```html
<section class="profile-card hp-card">
    <h2><i class="fa-solid fa-heart"></i> Points de Vie</h2>
    <div class="hp-display">
        <div class="hp-bar-container">
            <div class="hp-bar-fill" style="width: <?= min(100, round(($user['hp'] / $user['max_hp']) * 100)) ?>%"></div>
            <span class="hp-text"><?= (int)$user['hp'] ?> / <?= (int)$user['max_hp'] ?> PV</span>
        </div>
    </div>
</section>
```

Add CSS:
```css
.hp-card {
    text-align: center;
}
.hp-bar-container {
    position: relative;
    width: 100%;
    max-width: 300px;
    height: 28px;
    background: rgba(255,255,255,0.08);
    border-radius: 14px;
    overflow: hidden;
    margin: 15px auto 0;
    border: 1px solid rgba(255,255,255,0.1);
}
.hp-bar-fill {
    height: 100%;
    background: linear-gradient(90deg, #e74c3c, #2ecc71);
    border-radius: 14px;
    transition: width 0.5s ease;
}
.hp-text {
    position: absolute;
    inset: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
    font-size: 0.85rem;
    color: white;
    text-shadow: 0 1px 3px rgba(0,0,0,0.5);
}
```

### Step-by-Step
1. Open `profile.php`
2. Add the HP section after the stats section
3. Add the CSS for the HP bar
4. Test: visit profile → see HP bar with current/max values

---

## DB Migration Checklist

Before implementing, ensure these columns/tables exist:

1. **FundsGivenCount** on Users: `ALTER TABLE Users ADD COLUMN FundsGivenCount INT DEFAULT 0;`
   - Check if already applied. If not, run the SQL from `sprint3_migrations.sql` line 2.

2. **RiddleType** on Riddles: `ALTER TABLE Riddles ADD COLUMN RiddleType VARCHAR(50) DEFAULT 'MultipleChoice';`
   - Note: sprint3_migrations.sql has `DEFAULT 'Text'` which is wrong per our spec. Change to `DEFAULT 'MultipleChoice'`.
   - If already applied with 'Text', run: `ALTER TABLE Riddles ALTER COLUMN RiddleType SET DEFAULT 'MultipleChoice';` and `UPDATE Riddles SET RiddleType = 'MultipleChoice' WHERE RiddleType = 'Text' OR RiddleType IS NULL;`

3. **Comment** on Reviews: Already exists (VARCHAR, nullable). No migration needed.

4. **Demandes** table: Already exists (used by `demande_capital.php`). No migration needed.

5. **PasswordResets** table: From sprint3_migrations.sql. Check if already applied.

---

## Implementation Order (Recommended)

Execute in this order to minimize conflicts:

1. **F5** — Mage check at checkout (simple, isolated change)
2. **F10** — Admin FundsGivenCount (simple, isolated change)
3. **F2** — Item price validation (admin.php only)
4. **F6** — Comment field on reviews (soumettre_review.php + inventory.php)
5. **F7** — Player deletes own review (soumettre_review.php + details.php)
6. **F8** — Admin deletes player review (admin.php + new endpoint)
7. **F9** — Star distribution display (details.php)
8. **F17** — MaxHP UI (profile.php)
9. **F16** — Statistics chart (profile.php)
10. **F13** — Smart potion/spell use (inventory.php + inventory.js)
11. **F12** — Inventory filtering (inventory.php)
12. **F3** — Sort controls (index.php)
13. **F4** — Rarity + price filters (index.php)
14. **F1** — Currency conversion at checkout (confirmer_achat.php — most complex logic)
15. **F14/F15** — Enigma TrueFalse + ShortAnswer (multiple files, most invasive)
16. **F11** — Admin inventory viewer (admin.php + new endpoint)

---

## Testing Checklist

After all changes, manually test:

- [ ] Checkout with insufficient silver/bronze but enough gold → auto-converts (F1)
- [ ] Checkout with insufficient total value → error (F1)
- [ ] Admin adds item with 0/0/0 price → rejected (F2)
- [ ] Admin adds item with 101 gold price → rejected (F2)
- [ ] Sort by Name A-Z, Price desc, Rating desc, Rarity asc on catalog (F3)
- [ ] Filter by rarity "Rare" on catalog (F4)
- [ ] Filter by max price 20 gold on catalog (F4)
- [ ] Non-mage checkout with spell in cart → rejected (F5)
- [ ] Submit review with comment → Comment column populated (F6)
- [ ] Player deletes own review from details page (F7)
- [ ] Admin deletes any review from admin panel (F8)
- [ ] Star distribution percentages shown on details page (F9)
- [ ] Admin gives funds 3 times → 4th attempt blocked (F10)
- [ ] Admin views player inventory from admin panel (F11)
- [ ] Inventory search and type filter works (F12)
- [ ] Potion use with near-max HP shows waste warning (F13)
- [ ] TrueFalse riddle shows Vrai/Faux buttons (F14)
- [ ] ShortAnswer riddle shows text input (F15)
- [ ] Profile statistics shown as bar chart (F16)
- [ ] Profile shows HP bar with current/max (F17)
