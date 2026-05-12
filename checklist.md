# Checklist Darquest — Sprints 1, 2 et 3

## FONCTIONNALITÉS DARQUEST (Boutique / Items / Panier / Joueur)

### Sprint 1 — Priorité M (Obligatoire)

- [ ] Afficher tous les items avec les tris demandés
- [ ] Rechercher des items selon un critère avec les tris demandés
- [ ] Voir le détail d'un item
- [ ] Inscription d'un joueur
- [ ] Connexion d'un joueur
- [ ] Ajouter un item au panier
- [ ] Supprimer un item du panier
- [ ] Modifier la quantité d'un item dans le panier
- [ ] Consulter son panier
- [ ] Payer son panier
  - [ ] Mise à jour du solde du joueur
  - [ ] Mise à jour de l'inventaire du joueur
  - [ ] Mise à jour de la quantité en inventaire (items du magasin)
  - [ ] Vider le panier après paiement
- [ ] Consulter l'inventaire du joueur

### Sprint 1 — Priorité S (Supplémentaire)

- [ ] Rechercher des items selon plusieurs critères (critères combinés)

### Sprint 2 — Priorité M (Obligatoire)

- [ ] Dettes du sprint 1 (corriger les remarques de la revue du sprint 1)
- [ ] Augmenter ses points de vie en utilisant des sorts ou des potions
  - [ ] Les points de vie repris sont en fonction du type de sort
  - [ ] Un sort utilisé est soit supprimé de l'inventaire, soit sa quantité est réduite de 1
  - [ ] Une potion peut donner au maximum 5 points de vie
  - [ ] Une potion utilisée est soit supprimée de l'inventaire, soit sa quantité est réduite de 1

### Sprint 2 — Priorité S (Supplémentaire)

- [ ] Vendre un item (vers le magasin)
  - [ ] Items vendus à 60% de leur valeur initiale
  - [ ] Sorts vendus selon leur rareté : rareté 1 → 100%, rareté 2 → 95%, rareté 3 → 90%

### Sprint 3 — Toutes priorités confondues (choix selon vélocité)

- [ ] Dettes du sprint 2
- [ ] Valider le courriel d'un joueur à l'inscription — le courriel doit exister
- [ ] Réinitialiser son mot de passe
- [ ] Évaluer et commenter un item acheté par un joueur
- [ ] Un joueur peut retirer son commentaire
- [ ] Consulter les évaluations des items (lors de la recherche d'item)
- [ ] Un admin peut retirer un commentaire d'un joueur
- [ ] Afficher le pourcentage de personnes ayant évalué un item à un certain nombre d'étoiles (soigner l'affichage)
- [ ] Modifier le profil du joueur
- [ ] Ajouter des Items dans la BD (Admin)
- [ ] Augmenter le capital du joueur (Admin)
  - [ ] 1ère fois : 10 pièces d'or
  - [ ] 2ème fois : 10 pièces d'argent
  - [ ] 3ème fois : 10 pièces de bronze
- [ ] Voir l'inventaire des joueurs (Admin)
- [ ] Fixer le nombre de points de vie maximum pour un joueur
  - [ ] Utilisation intelligente des potions/sorts (ex: si max=100 et joueur à 95, utiliser potion de 5 pts plutôt que sort de 50 pts)
  - [ ] Ne pas utiliser un sort ou une potion si le max de pts de vie est atteint

---

## FONCTIONNALITÉS ENIGMA (Énigmes / Quêtes)

### Sprint 2 — Priorité M (Obligatoire)

- [ ] Application Web adaptative (adaptée pour un cellulaire)
- [ ] Enigma : Résoudre une énigme de manière aléatoire (énigme aléatoire, difficulté aléatoire)
- [ ] Contrainte : 4 choix de réponses dont une seule bonne réponse
  - [ ] Énigme difficile → 10 pièces d'or
  - [ ] Énigme moyenne → 10 pièces d'argent
  - [ ] Énigme facile → 10 pièces de bronze
- [ ] Perte des points de vie lorsqu'un joueur donne une mauvaise réponse
  - [ ] Énigme facile → perd 3 pts de vie
  - [ ] Énigme moyenne → perd 6 pts de vie
  - [ ] Énigme difficile → perd 10 pts de vie
- [ ] Devenir « mage » : lorsqu'un joueur a résolu 3 quêtes en rapport avec la magie (Sorts)

### Sprint 2 — Priorité S (Supplémentaire)

- [ ] Enigma : Résoudre une énigme en choisissant la difficulté (le joueur choisit, l'énigme est aléatoire)
  - [ ] Énigme difficile → 10 pièces d'or
  - [ ] Énigme moyenne → 10 pièces d'argent
  - [ ] Énigme facile → 10 pièces de bronze
- [ ] Bonus : 3 énigmes difficiles réussies successivement → gagner 100 pièces d'or

### Sprint 2 — Priorité C (Complémentaire)

- [ ] Enigma : Afficher les statistiques du joueur dans Enigma (nb quêtes réussies / nb quêtes totales selon la difficulté)
- [ ] Enigma : Ajouter des énigmes et leurs réponses dans la BD (Admin)

### Sprint 3

- [ ] Enigma : Avoir d'autres types d'énigme
  - [ ] Énigme avec réponse vrai ou faux
  - [ ] Énigme avec réponse une phrase courte
- [ ] Enigma : Statistiques sous forme de graphique

---

## RÈGLES MÉTIER (À considérer — Sprint 1)

- [ ] Prix d'un item : de 1 à 100 pièces d'or
- [ ] Montant initial d'un joueur : 1000 pièces d'or, 1000 pièces d'argent, 1000 pièces de bronze
- [ ] Conversion : 1 pièce d'or = 10 pièces d'argent, 1 pièce d'argent = 10 pièces de bronze
- [ ] Seul un mage peut acheter un sort (non-mage → erreur)

---

## STORIES TECHNIQUES (Sprint 1)

- [ ] Vérifier la connexion au serveur PHP (chaque membre teste)
- [ ] Chaque membre doit tester qu'il peut transférer une page sur le serveur
- [ ] Créer et peupler la base de données
  - [ ] Au moins 5 joueurs
  - [ ] Au moins 5 items de chaque type (utiliser procédure stockée pour simplifier l'insertion)
- [ ] Configurer GitHub
  - [ ] Chaque membre doit avoir un compte GitHub
  - [ ] Un membre crée le dépôt et invite les coéquipiers
- [ ] Créer le tableau de tâches verticales pour le sprint 1 (post-it au mur)
- [ ] Créer le Trello/Jira pour le sprint 1 (un membre crée et invite les coéquipiers)

---

## TESTS UNITAIRES

### Sprint 1 — Tests sur le compte du joueur

- [ ] Validation de tous les champs
- [ ] L'alias est unique
- [ ] Le courriel est unique (s'il y a lieu)
- [ ] Afficher les cas d'erreur (connexion multiple, alias dupliqué, etc.)
- [ ] Connexion réussie : afficher les infos pertinentes du joueur
- [ ] Connexion non réussie : afficher le message d'erreur

### Sprint 1 — Tests sur les recherches d'item

- [ ] Recherche sans critère
- [ ] Recherche avec tous les critères
- [ ] Recherche selon un critère (ex: Potion — tous les critères testés)
- [ ] Recherche selon deux critères (ex: Potion et Arme — toutes les combinaisons testées)
- [ ] Recherche selon trois critères (toutes les combinaisons testées)
- [ ] Afficher les détails pertinents pour un item
- [ ] Trier le résultat de la recherche
- [ ] Interface conviviale et intuitive, facilité de navigation

### Sprint 1 — Tests sur le panier

- [ ] Ajouter un item au panier : quantité disponible, montant affiché, montant total du panier affiché
- [ ] Modifier la quantité d'un item : quantité disponible, montant mis à jour, montant total du panier mis à jour
- [ ] Supprimer un item du panier : montant total du panier mis à jour
- [ ] Ajouter un item au panier, quantité non disponible
- [ ] Modifier la quantité du panier, quantité non disponible
- [ ] Payer son panier : le joueur a le montant
  - [ ] Pièces d'or, d'argent, bronze mises à jour
  - [ ] Inventaire du joueur mis à jour
  - [ ] Magasin (quantité item) mis à jour
  - [ ] Panier vidé
- [ ] Payer son panier : le joueur n'a pas le montant → indiquer solde insuffisant
- [ ] Acheter sans se connecter → erreur
- [ ] Acheter un sort si le joueur n'est pas mage → erreur

### Sprint 1 — Tests sur l'inventaire du joueur

- [ ] Aucun critère : afficher tous les items
- [ ] (Optionnel) Afficher selon un critère (ex: Potion — tous les critères testés)
- [ ] (Optionnel) Afficher selon des critères combinés

### Sprint 2 — Tests

- [ ] Maintenir une batterie de tests unitaires pour le sprint en cours
- [ ] Tests d'acceptation détaillés (tenir compte des remarques du sprint zéro, sur les post-it)

### Sprint 3 — Tests

- [ ] Au moins 20 tests unitaires pour le sprint 3

---

## GESTION DE PROJET / BACKLOG

- [ ] Sprint 1 : Backlog du sprint 1 (modifié selon les fonctionnalités du sprint 1)
- [ ] Sprint 2 : Backlog du sprint 2 (à remettre le jour du début du sprint 2)
- [ ] Sprint 2 : Tests d'acceptation détaillés sur les post-it
- [ ] Sprint 3 : Backlog du sprint 3 (remise avant le 23 avril minuit)
  - [ ] Tableau avec colonnes : id, Énoncé, Test d'acceptation, P, E
  - [ ] Stories priorisées, estimées (planning poker)
  - [ ] Tests d'acceptation présents et bien écrits
- [ ] Sprint 3 : Partager le Trello ou Jira avec le professeur
