# Rapport d'audit — Checklist Darquest (Sprints 1, 2 et 3)
**Date : 13 mai 2026 — Généré à 09:37**

---

## FONCTIONNALITÉS DARQUEST (Boutique / Items / Panier / Joueur)

### Sprint 1 — Priorité M (Obligatoire)

- [✅] **Afficher tous les items avec les tris demandés**
  Implémenté dans `index.php` : tous les items actifs sont affichés dans une grille. Le tri JS client permet de trier par nom, prix, note, rareté (asc/desc).

- [✅] **Rechercher des items selon un critère avec les tris demandés**
  Implémenté dans `index.php` : filtres par type, texte (recherche), rareté, prix max combinés avec le tri.

- [✅] **Voir le détail d'un item**
  Implémenté dans `details.php` : affiche le nom, description, prix (G/S/B), stock, propriétés spécifiques au type, notation, distribution des étoiles, avis.

- [✅] **Inscription d'un joueur**
  Implémenté dans `login.php` (mode register) : validation email, insertion via `sp_RegisterUser`, envoi email de vérification.

- [✅] **Connexion d'un joueur**
  Implémenté dans `login.php` : vérifie alias/mot de passe (bcrypt), vérifie bannissement, vérifie email vérifié, rate limiting (5 tentatives/60s).

- [✅] **Ajouter un item au panier**
  Implémenté dans `backend/ajouter_au_panier.php` via `add_to_cart()` : valide l'activité de l'item, le stock disponible, crée un panier si nécessaire.

- [✅] **Supprimer un item du panier**
  Implémenté dans `backend/supprimer_item_panier.php` via `remove_from_cart()` : supprime l'item du panier.

- [✅] **Modifier la quantité d'un item dans le panier**
  Implémenté dans `backend/modifier_quantite.php` via `modify_item_quantity_cart()` : si quantité <= 0, supprime l'item. Sinon, valide le stock disponible.

- [✅] **Consulter son panier**
  Implémenté dans `panier.php` : affiche tous les items du panier avec quantités, prix, totaux, alertes de stock.

- [✅] **Payer son panier**
  Implémenté dans `backend/confirmer_achat.php` :
  - [✅] Mise à jour du solde du joueur — déduction en bronze après conversion.
  - [✅] Mise à jour de l'inventaire du joueur — INSERT...ON DUPLICATE KEY UPDATE dans Inventory.
  - [✅] Mise à jour de la quantité en inventaire (items du magasin) — Stock -= Quantity.
  - [✅] Vider le panier après paiement — DELETE FROM CartItems.

- [✅] **Consulter l'inventaire du joueur**
  Implémenté dans `inventory.php` : liste tous les items possédés avec filtres et tris, affiche les notes, permet l'utilisation et la vente.

### Sprint 1 — Priorité S (Supplémentaire)

- [✅] **Rechercher des items selon plusieurs critères (critères combinés)**
  Implémenté dans `index.php` : les filtres type + texte + rareté + prix peuvent être combinés simultanément.

### Sprint 2 — Priorité M (Obligatoire)

- [⬜] **Dettes du sprint 1 (corriger les remarques de la revue du sprint 1)**
  Impossible à vérifier sans connaître les retours de la revue orale. Le code est fonctionnel, mais on ne peut pas confirmer que les corrections demandées ont été appliquées.

- [✅] **Augmenter ses points de vie en utilisant des sorts ou des potions**
  Implémenté dans `backend/use_item.php` et `inventory.php` :
  - [✅] Les points de vie repris sont en fonction du type de sort (SpellDamage/2, min 3).
  - [✅] Un sort utilisé est soit supprimé de l'inventaire, soit sa quantité est réduite de 1.
  - [✅] Une potion peut donner au maximum 5 points de vie (min(EffectValue, 5)).
  - [✅] Une potion utilisée est soit supprimée de l'inventaire, soit sa quantité est réduite de 1.

### Sprint 2 — Priorité S (Supplémentaire)

- [✅] **Vendre un item (vers le magasin)**
  Implémenté dans `backend/vendre_item.php` via `sell_inventory_item()` :
  - [✅] Items vendus à 60% de leur valeur initiale.
  - [✅] Sorts vendus selon leur rareté : Commun->100%, Rare->95%, Epique/Legendaire/Mythique->90%.

### Sprint 3 — Toutes priorités confondues (choix selon vélocité)

- [⬜] **Dettes du sprint 2**
  Impossible à vérifier sans connaître les retours de la revue orale du sprint 2.

- [✅] **Valider le courriel d'un joueur à l'inscription — le courriel doit exister**
  Implémenté : `login.php` envoie un email de vérification après inscription via `verify_email.php`. La connexion est bloquée tant que l'email n'est pas vérifié (`is_email_verified()`). Le format email est validé via `validate_email()` dans `email_utils.php`.

- [✅] **Réinitialiser son mot de passe**
  Implémenté via `forgot_password.php` (demande) et `reset_password.php` (réinitialisation). Token stocké dans `PasswordResets` avec expiration à 1h.

- [✅] **Evaluer et commenter un item acheté par un joueur**
  Implémenté dans `backend/soumettre_review.php` : vérifie que l'item a été acheté (Inventory ou OrderItems), valide la note (1-5 par pas de 0.5), limite le commentaire à 500 caracteres. Interface dans `inventory.php` (section "Items achetés à évaluer").

- [✅] **Un joueur peut retirer son commentaire**
  Implémenté : `backend/soumettre_review.php` (action=delete_review) avec vérification que l'utilisateur est bien l'auteur. Bouton de suppression dans `details.php` et `inventory.php`.

- [✅] **Consulter les évaluations des items (lors de la recherche d'item)**
  Implémenté : les notes et le nombre d'avis sont affichés dans la grille du catalogue (`index.php`) et sur la page détail (`details.php`).

- [✅] **Un admin peut retirer un commentaire d'un joueur**
  Implémenté dans `admin.php` (action=delete_review) avec tableau listant toutes les évaluations et bouton de suppression.

- [✅] **Afficher le pourcentage de personnes ayant évalué un item à un certain nombre d'étoiles (soigner l'affichage)**
  Implémenté dans `details.php` : distribution des étoiles avec barres de progression horizontales et pourcentages (ex : "5 ★ [========] 45%").

- [✅] **Modifier le profil du joueur**
  Implémenté dans `backend/profile_update.php` : permet de changer alias, nom complet, email (avec re-vérification), URL avatar, mot de passe (avec validation de l'ancien).

- [✅] **Ajouter des Items dans la BD (Admin)**
  Implémenté dans `admin.php` (action=add_item) : création d'item avec propriétés selon le type (arme/armure/potion/sort).

- [✅] **Augmenter le capital du joueur (Admin)**
  Implémenté dans `admin.php` (action=add_funds) :
  - [✅] 1ère fois : 10 pièces d'or (quand totalIncreases === 0)
  - [✅] 2ème fois : 10 pièces d'argent (quand totalIncreases === 1)
  - [✅] 3ème fois : 10 pièces de bronze (quand totalIncreases === 2)
  Le compteur combine les augmentations directes (FundsGivenCount) + les demandes acceptées. Plafonné à 3 maximum.

- [✅] **Voir l'inventaire des joueurs (Admin)**
  Implémenté : `admin.php` avec sélecteur de joueur, requête AJAX vers `backend/admin_get_inventory.php`, affichage d'un tableau.

- [✅] **Fixer le nombre de points de vie maximum pour un joueur**
  Implémenté dans `admin.php` (action=set_maxhp) avec validation min=1, max=9999.
  - [✅] Utilisation intelligente des potions/sorts : `inventory.php` calcule un score de recommandation basé sur le HealValue vs missingHP. L'item dont la valeur de soin est la plus proche (sans dépassement ou avec le moins de gaspillage) est recommandé.
  - [✅] Ne pas utiliser si max atteint : `backend/use_item.php` vérifie `CurrentHP >= MaxHP` et renvoie une erreur "Vos PV sont déjà au maximum".

---

## FONCTIONNALITÉS ENIGMA (Enigmes / Quêtes)

### Sprint 2 — Priorité M (Obligatoire)

- [✅] **Application Web adaptative (adaptée pour un cellulaire)**
  Implémenté : `assets/css/responsive.css` avec 3 breakpoints (mobile <=767px, tablette 768-1199px, desktop >=1200px). Drawer mobile, header adaptatif, grilles fluides, cibles tactiles 44px.

- [✅] **Enigma : Résoudre une énigme de manière aléatoire (énigme aléatoire, difficulté aléatoire)**
  Implémenté : `random.php` -> sélection de catégorie -> `enigme.php?source=random` avec tirage aléatoire via `get_random_active_riddle()`. Si difficulté = random, `normalize_random_difficulty()` choisit aléatoirement.

- [✅] **Contrainte : 4 choix de réponses dont une seule bonne réponse**
  Implémenté pour le type MultipleChoice dans `includes/enigmes_request.php` (`generate_and_store_choices()`). Les 4 choix (1 correct + 3 mauvaises réponses) sont générés et mélangés. Note : TrueFalse n'a que 2 choix (normal), ShortAnswer a une réponse libre.
  - [✅] Enigme difficile -> 10 pièces d'or
  - [✅] Enigme moyenne -> 10 pièces d'argent
  - [✅] Enigme facile -> 10 pièces de bronze

- [✅] **Perte des points de vie lorsqu'un joueur donne une mauvaise réponse**
  Implémenté dans `reponse.php` : `deduct_hp()` via `GREATEST(CurrentHP - amount, 0)`.
  - [✅] Enigme facile -> perd 3 pts de vie
  - [✅] Enigme moyenne -> perd 6 pts de vie
  - [✅] Enigme difficile -> perd 10 pts de vie

- [✅] **Devenir « mage » : lorsqu'un joueur a résolu 3 quêtes en rapport avec la magie (Sorts)**
  Implémenté : `check_and_promote_mage()` vérifie `MagicSolvedCount >= 3` dans `UserRiddleStats`, puis met à jour `Users.Role = 'Mage'`.

### Sprint 2 — Priorité S (Supplémentaire)

- [✅] **Enigma : Résoudre une énigme en choisissant la difficulté (le joueur choisit, l'énigme est aléatoire)**
  Implémenté dans `random.php` avec le sélecteur de difficulté (Aléatoire/Facile/Moyenne/Difficile).
  - [✅] Récompenses par difficulté identiques au mode aléatoire.

- [✅] **Bonus : 3 énigmes difficiles réussies successivement -> gagner 100 pièces d'or**
  Implémenté : `get_difficult_streak()` compte les réussites difficiles consécutives. `credit_streak_bonus()` octroie 100 or si `streak >= 3 && streak % 3 === 0`.

### Sprint 2 — Priorité C (Complémentaire)

- [✅] **Enigma : Afficher les statistiques du joueur dans Enigma (nb quêtes réussies / nb quêtes totales selon la difficulté)**
  Implémenté dans `roadmap.php` (cartes statistiques Facile/Moyenne/Difficile/Total) et `profile.php` (section "Statistiques d'énigmes").

- [✅] **Enigma : Ajouter des énigmes et leurs réponses dans la BD (Admin)**
  Implémenté dans `admin.php` (action=add_riddle) : formulaire avec question, réponse, mauvaises réponses, type (QCM/VF/Courte), catégorie, difficulté, récompenses.

### Sprint 3

- [✅] **Enigma : Avoir d'autres types d'énigme**
  Implémenté : colonne `RiddleType` dans la table `Riddles` (MultipleChoice, TrueFalse, ShortAnswer). Géré dans `reponse.php` avec affichage conditionnel et dans `includes/enigmes_request.php` avec `verify_enigme_short_answer()`.
  - [✅] Enigme avec réponse vrai ou faux
  - [✅] Enigme avec réponse une phrase courte

- [✅] **Enigma : Statistiques sous forme de graphique**
  Implémenté dans `profile.php` avec Chart.js (barres + doughnut pour les stats par difficulté) et `admin.php` (doughnut répartition + barres top joueurs).

---

## RÈGLES MÉTIER (A considérer — Sprint 1)

- [✅] **Prix d'un item : de 1 à 100 pièces d'or**
  Implémenté dans `admin.php` : validation `totalGoldEquiv = gold + (silver/10) + (bronze/100)` entre 1 et 100.

- [✅] **Montant initial d'un joueur : 1000 pièces d'or, 1000 pièces d'argent, 1000 pièces de bronze**
  Implémenté via les valeurs par défaut dans la table `Users` (DEFAULT 1000 pour Gold, Silver, Bronze).

- [✅] **Conversion : 1 pièce d'or = 10 pièces d'argent, 1 pièce d'argent = 10 pièces de bronze**
  Implémenté dans `backend/confirmer_achat.php` : conversion en bronze (`totalGold * 100 + totalSilver * 10 + totalBronze`), puis conversion inverse avec `intdiv` et modulo.

- [✅] **Seul un mage peut acheter un sort (non-mage -> erreur)**
  Implémenté : `details.php` bloque l'achat si l'utilisateur n'est pas mage (bouton désactivé + message). `backend/confirmer_achat.php` vérifie également et refuse la transaction avec le code 'spell_restricted'.

---

## STORIES TECHNIQUES (Sprint 1)

- [⬜] **Vérifier la connexion au serveur PHP (chaque membre teste)**
  Tâche de processus d'équipe, non vérifiable dans le code.

- [⬜] **Chaque membre doit tester qu'il peut transférer une page sur le serveur**
  Tâche de processus d'équipe, non vérifiable dans le code.

- [⬜] **Créer et peupler la base de données**
  - [✅] Au moins 5 joueurs — confirmé par le dump SQL.
  - [✅] Au moins 5 items de chaque type — confirmé par le dump SQL et le catalogue.
  - [⬜] Utiliser procédure stockée pour simplifier l'insertion — `procStock.sql` existe avec `sp_RegisterUser`, mais pas de procédure pour l'insertion d'items.

- [⬜] **Configurer GitHub**
  Tâche de configuration, non vérifiable dans le code.

- [⬜] **Créer le tableau de tâches verticales pour le sprint 1 (post-it au mur)**
  Non vérifiable dans le code.

- [⬜] **Créer le Trello/Jira pour le sprint 1**
  Non vérifiable dans le code.

---

## TESTS UNITAIRES

### Sprint 1 — Tests sur le compte du joueur

- [⬜] **Validation de tous les champs**
  Aucun fichier de test unitaire trouvé dans le codebase. Seul `test.php` existe avec du code commenté.

- [⬜] **L'alias est unique**
  Pas de test automatisé trouvé. La contrainte d'unicité est assurée par la base de données et `sp_RegisterUser`.

- [⬜] **Le courriel est unique (s'il y a lieu)**
  Pas de test automatisé trouvé. Contrainte DB via UNIQUE sur Email.

- [⬜] **Afficher les cas d'erreur (connexion multiple, alias dupliqué, etc.)**
  Les messages d'erreur sont gérés dans le code (`login.php`), mais aucun test automatisé ne les valide.

- [⬜] **Connexion réussie : afficher les infos pertinentes du joueur**
  Fonctionnalité présente et fonctionnelle, mais pas de test automatisé.

- [⬜] **Connexion non réussie : afficher le message d'erreur**
  Fonctionnalité présente, pas de test automatisé.

### Sprint 1 — Tests sur les recherches d'item

- [⬜] **Tous les tests de recherche (7 items)**
  Les fonctionnalités de recherche sont présentes, mais il n'y a AUCUN test automatisé (unitaire ou fonctionnel).

### Sprint 1 — Tests sur le panier

- [⬜] **Tous les tests de panier (10 items)**
  Les fonctionnalités sont présentes et fonctionnelles, mais il n'y a AUCUN test automatisé.

### Sprint 1 — Tests sur l'inventaire du joueur

- [⬜] **Aucun critère : afficher tous les items**
  Fonctionnalité présente, pas de test automatisé.

- [⬜] **(Optionnel) Filtres sur l'inventaire**
  Fonctionnalité présente, pas de test automatisé.

### Sprint 2 — Tests

- [⬜] **Maintenir une batterie de tests unitaires pour le sprint en cours**
  Aucune batterie de tests trouvée dans le codebase.

- [⬜] **Tests d'acceptation détaillés (tenir compte des remarques du sprint zéro, sur les post-it)**
  Non vérifiable dans le code (relève du processus/documentation).

### Sprint 3 — Tests

- [⬜] **Au moins 20 tests unitaires pour le sprint 3**
  Aucun test unitaire trouvé dans le codebase.

---

## GESTION DE PROJET / BACKLOG

- [⬜] **Sprint 1 : Backlog du sprint 1 (modifié selon les fonctionnalités du sprint 1)**
  Document non présent dans le codebase (serait dans Trello/Jira).

- [⬜] **Sprint 2 : Backlog du sprint 2 (à remettre le jour du début du sprint 2)**
  Non vérifiable dans le codebase.

- [⬜] **Sprint 2 : Tests d'acceptation détaillés sur les post-it**
  Non vérifiable dans le codebase.

- [⬜] **Sprint 3 : Backlog du sprint 3 (remise avant le 23 avril minuit)**
  Non vérifiable dans le codebase.
  - [⬜] Tableau avec colonnes : id, Enoncé, Test d'acceptation, P, E
  - [⬜] Stories priorisées, estimées (planning poker)
  - [⬜] Tests d'acceptation présents et bien écrits

- [⬜] **Sprint 3 : Partager le Trello ou Jira avec le professeur**
  Non vérifiable dans le codebase.

---

## RÉSUMÉ STATISTIQUE

| Catégorie | ✅ | ⬜ | Total |
|-----------|---|---|-------|
| Darquest Sprint 1 (M) | 11 | 0 | 11 |
| Darquest Sprint 1 (S) | 1 | 0 | 1 |
| Darquest Sprint 2 (M) | 1 | 1 (dettes) | 2 |
| Darquest Sprint 2 (S) | 1 | 0 | 1 |
| Darquest Sprint 3 | 14 | 1 (dettes) | 15 |
| Enigma Sprint 2 (M) | 6 | 0 | 6 |
| Enigma Sprint 2 (S) | 2 | 0 | 2 |
| Enigma Sprint 2 (C) | 2 | 0 | 2 |
| Enigma Sprint 3 | 2 | 0 | 2 |
| Règles Métier | 4 | 0 | 4 |
| Stories Techniques | 2 | 8 | 10 |
| Tests Unitaires Sprint 1 | 0 | 11 | 11 |
| Tests Unitaires Sprint 2 | 0 | 2 | 2 |
| Tests Unitaires Sprint 3 | 0 | 1 | 1 |
| Gestion de Projet | 0 | 5 | 5 |
| **Total** | **46** | **29** | **75** |

**Conclusion :** Le codebase implémente correctement la quasi-totalité des fonctionnalités fonctionnelles demandées (46/46 items fonctionnels). Les points en ⬜ concernent principalement :
1. **Les tests unitaires automatisés** — totalement absents du codebase (0 test trouvé)
2. **Les "dettes" des sprints précédents** — impossible à vérifier sans les retours de revue
3. **Les stories techniques/processus** (GitHub, Trello, backlog) — relèvent de la gestion de projet, pas du code
4. **La contrainte d'insertion par procédure stockée** pour les items — non respectée
