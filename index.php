<?php
require_once __DIR__ . '/config/config.php';

$title = "L'Arsenal - Marché Noir";

$items = [
    ['id' => 1, 'nom' => "Lame de l'Exilé", 'type' => 'arme', 'rarete' => 'Rare', 'prix' => 1200, 'stock' => 3, 'rating' => 4, 'reviews' => 12],
    ['id' => 2, 'nom' => "Potion de Hâte", 'type' => 'potion', 'rarete' => 'Commun', 'prix' => 150, 'stock' => 0, 'rating' => 2, 'reviews' => 45],
];
?>



<?php include __DIR__ . '/templates/head.php'; ?>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="wrapper">
    <aside id="sidebar">
        <button id="toggle-btn" onclick="toggleMenu()">
            <span id="arrow-icon">«</span>
        </button>
<div class="wrapper">
    <aside id="sidebar">
        <button id="toggle-btn" onclick="toggleMenu()">
            <span id="arrow-icon">«</span>
        </button>

        <div class="sidebar-content">
            <div class="show-icon">🔍</div>

            <div class="hide-text">
                <form class="filter-section">
                    <div class="filter-group">
                        <label>Catégorie</label>
                        <select name="type">
                            <option value="all">Tous les items</option>
                            <option value="arme">Armes</option>
                            <option value="armure">Armures</option>
                            <option value="potion">Potions</option>
                            <option value="sort">Sorts</option>
                        </select>
                    </div>

                    <button type="submit" style="width:100%; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">
                        Filtrer
                    </button>
                </form>
            </div>
        <div class="sidebar-content">
            <div class="show-icon">🔍</div>

            <div class="hide-text">
                <form class="filter-section">
                    <div class="filter-group">
                        <label>Catégorie</label>
                        <select name="type">
                            <option value="all">Tous les items</option>
                            <option value="arme">Armes</option>
                            <option value="armure">Armures</option>
                            <option value="potion">Potions</option>
                            <option value="sort">Sorts</option>
                        </select>
                    </div>

                    <button type="submit" style="width:100%; background:transparent; border:1px solid var(--accent); color:var(--accent); padding:10px; cursor:pointer; border-radius:4px; font-weight:bold;">
                        Filtrer
                    </button>
                </form>
            </div>

            <div class="cta-box">
                <div class="hide-text">
                    <?php if ($user['isConnected']): ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Essais énigmes : <b style="color:var(--accent)">5 / 5</b></p>
                    <?php else: ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Besoin d'or ?</p>
                    <?php endif; ?>

                    <a href="#" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
                </div>
            </div>
        </div>
    </aside>
            <div class="cta-box">
                <div class="hide-text">
                    <?php if ($user['isConnected']): ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Essais énigmes : <b style="color:var(--accent)">5 / 5</b></p>
                    <?php else: ?>
                        <p style="margin:0 0 8px 0; font-size:0.9rem;">Besoin d'or ?</p>
                    <?php endif; ?>

                    <a href="#" style="color:var(--accent); text-decoration:none; font-weight:bold; font-size:0.85rem;">Résoudre des énigmes</a>
                </div>
            </div>
        </div>
    </aside>

    <main>
        <div class="catalog-banner">
            <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.3rem;">
                <?= $user['isConnected'] ? "Content de vous revoir, " . $user['alias'] : "Catalogue des Reliques" ?>
            </h2>
        </div>
    <main>
        <div class="catalog-banner">
            <h2 style="margin:0; text-transform:uppercase; letter-spacing:2px; font-size:1.3rem;">
                <?= $user['isConnected'] ? "Content de vous revoir, " . $user['alias'] : "Catalogue des Reliques" ?>
            </h2>
        </div>

        <div class="product-list">
            <?php foreach ($items as $item): ?>
                <div class="item-row" <?= ($item['stock'] == 0) ? 'style="opacity: 0.7;"' : '' ?>>
                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h3><?= $item['nom'] ?></h3>
                            <span style="background: rgba(25, 133, 161, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;"><?= $item['rarete'] ?></span>
                        </div>

                        <div style="margin-top: 5px;">
                            <span style="color: var(--gold);">★ ★ ★ ★ ☆</span>
                            <small style="color: var(--text-silver); margin-left: 5px;">(<?= $item['reviews'] ?> aventuriers)</small>
                        </div>
                    </div>
        <div class="product-list">
            <?php foreach ($items as $item): ?>
                <div class="item-row" <?= ($item['stock'] == 0) ? 'style="opacity: 0.7;"' : '' ?>>
                    <div class="item-info">
                        <div style="display: flex; align-items: center; gap: 10px;">
                            <h3><?= $item['nom'] ?></h3>
                            <span style="background: rgba(25, 133, 161, 0.2); color: var(--accent); padding: 2px 8px; border-radius: 4px; font-size: 0.7rem; font-weight: bold;"><?= $item['rarete'] ?></span>
                        </div>

                        <div style="margin-top: 5px;">
                            <span style="color: var(--gold);">★ ★ ★ ★ ☆</span>
                            <small style="color: var(--text-silver); margin-left: 5px;">(<?= $item['reviews'] ?> aventuriers)</small>
                        </div>
                    </div>

                    <div style="text-align: right;">
                        <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>

                        <?php if ($item['stock'] > 0): ?>
                            <small style="color: #2ECC71; font-weight: bold;">En stock: <?= $item['stock'] ?></small>
                        <?php else: ?>
                            <small style="color: #E74C3C; font-weight: bold;">Rupture de stock</small>
                        <?php endif; ?>
                    </div>
                    <div style="text-align: right;">
                        <div class="item-price"><?= number_format($item['prix'], 0) ?> GP</div>

                        <?php if ($item['stock'] > 0): ?>
                            <small style="color: #2ECC71; font-weight: bold;">En stock: <?= $item['stock'] ?></small>
                        <?php else: ?>
                            <small style="color: #E74C3C; font-weight: bold;">Rupture de stock</small>
                        <?php endif; ?>
                    </div>

                    <div class="item-action-btns" style="margin-left: 20px;">
                        <?php if ($user['isConnected']): ?>
                            <?php if ($item['stock'] == 0): ?>
                                <button disabled style="background:#444; cursor:not-allowed;">Épuisé</button>
                            <?php elseif ($item['type'] == 'sort' && !$user['isMage']): ?>
                                <button disabled title="Niveau Mage requis" style="background:#666; font-size:0.7rem;">Mage Requis</button>
                            <?php else: ?>
                                <button onclick="window.location.href='details.php?id=<?= $item['id'] ?>'" style="padding: 5px 12px; font-size: 0.8rem; background:var(--accent); color:white; border:none; border-radius:4px; cursor:pointer;">Acheter</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="details.php?id=<?= $item['id'] ?>" style="text-decoration:none; color:var(--accent); font-size:1.5rem;">➔</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
                    <div class="item-action-btns" style="margin-left: 20px;">
                        <?php if ($user['isConnected']): ?>
                            <?php if ($item['stock'] == 0): ?>
                                <button disabled style="background:#444; cursor:not-allowed;">Épuisé</button>
                            <?php elseif ($item['type'] == 'sort' && !$user['isMage']): ?>
                                <button disabled title="Niveau Mage requis" style="background:#666; font-size:0.7rem;">Mage Requis</button>
                            <?php else: ?>
                                <button onclick="window.location.href='details.php?id=<?= $item['id'] ?>'" style="padding: 5px 12px; font-size: 0.8rem; background:var(--accent); color:white; border:none; border-radius:4px; cursor:pointer;">Acheter</button>
                            <?php endif; ?>
                        <?php else: ?>
                            <a href="details.php?id=<?= $item['id'] ?>" style="text-decoration:none; color:var(--accent); font-size:1.5rem;">➔</a>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <div class="pagination">
            <a href="#">&laquo; Précédent</a>
            <span>Page <strong>1</strong> sur 12</span>
            <a href="#">Suivant &raquo;</a>
        </div>
    </main>
</div>
        <div class="pagination">
            <a href="#">&laquo; Précédent</a>
            <span>Page <strong>1</strong> sur 12</span>
            <a href="#">Suivant &raquo;</a>
        </div>
    </main>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>
<?php include __DIR__ . '/includes/footer.php'; ?>
<?php include __DIR__ . '/templates/end.php'; ?>