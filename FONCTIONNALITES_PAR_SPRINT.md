# Liste des fonctionnalites par sprint

Ce fichier liste toutes les actions qu'un utilisateur doit pouvoir effectuer sur le site pour couvrir 100% de la checklist Darquest, organisees par sprint selon les fichiers sprint1.md, sprint2.md et sprint3.md.

---

## SPRINT 1

### Fonctionnalites Joueur

1. Afficher tous les items avec les tris (par nom, prix, note, rarete, ordre asc/desc)
2. Rechercher des items selon un critere avec les tris
3. Rechercher des items selon plusieurs criteres (type + texte + rarete + prix combines)
4. Voir le detail d'un item (description, proprietes selon le type, stock, note)
5. Inscription d'un joueur (alias + email + mot de passe)
6. Connexion d'un joueur (alias + mot de passe)
7. Ajouter un item au panier
8. Supprimer un item du panier
9. Modifier la quantite d'un item dans le panier
10. Consulter son panier (quantites, montant total)
11. Payer son panier
    - Mise a jour du solde du joueur (or/argent/bronze)
    - Mise a jour de l'inventaire du joueur
    - Mise a jour de la quantite en stock dans le magasin
    - Vider le panier apres paiement
12. Payer sans avoir le montant : message solde insuffisant
13. Acheter sans etre connecte : erreur / redirection login
14. Acheter un sort si le joueur n'est pas mage : erreur
15. Consulter l'inventaire du joueur (tous les items possedes)

### Regles Metier

16. Prix d'un item : de 1 a 100 pieces d'or (equivalent total)
17. Montant initial d'un joueur : 1000 pieces d'or, 1000 pieces d'argent, 1000 pieces de bronze
18. Conversion : 1 piece d'or = 10 pieces d'argent, 1 piece d'argent = 10 pieces de bronze
19. Seul un mage peut acheter un sort (non-mage : erreur)

### Tests Unitaires (Sprint 1)

**Compte joueur :**
20. Valider que tous les champs du formulaire d'inscription sont valides
21. Verifier que l'alias est unique
22. Verifier que le courriel est unique
23. Afficher les cas d'erreur (alias duplique, email duplique, connexion multiple)
24. Connexion reussie : afficher les infos pertinentes du joueur
25. Connexion non reussie : afficher le message d'erreur

**Recherche d'items :**
26. Recherche sans aucun critere (tout afficher)
27. Recherche avec tous les criteres actifs
28. Recherche selon un seul critere (tester chaque type individuellement)
29. Recherche selon deux criteres (tester toutes les combinaisons)
30. Recherche selon trois criteres (tester toutes les combinaisons)
31. Afficher les details pertinents pour un item
32. Trier le resultat de la recherche
33. Interface conviviale et intuitive, facilite de navigation

**Panier :**
34. Ajouter un item : quantite disponible, montant affiche, montant total affiche
35. Modifier la quantite : quantite dispo, montant mis a jour, total mis a jour
36. Supprimer un item : montant total mis a jour
37. Ajouter un item avec quantite non disponible
38. Modifier la quantite avec quantite non disponible
39. Payer : le joueur a le montant : or/argent/bronze mis a jour, inventaire mis a jour, stock magasin mis a jour, panier vide
40. Payer : le joueur n'a pas le montant : message solde insuffisant
41. Acheter sans se connecter : erreur
42. Acheter un sort sans etre mage : erreur

**Inventaire :**
43. Aucun critere : afficher tous les items
44. (Optionnel) Afficher selon un critere
45. (Optionnel) Afficher selon des criteres combines

---

## SPRINT 2

### Application Web Adaptative

46. L'application (notamment Enigma) est adaptee pour un affichage sur cellulaire
    - Menu hamburger sur mobile
    - Drawer de navigation
    - Grille responsive (1 colonne mobile, 2 tablette, 4 desktop)
    - Boutons adaptes au tactile (taille minimale 44px)
    - Elements decoratifs caches sur mobile

### Enigma : Resoudre une enigme (Priorite M)

47. Resoudre une enigme tiree aleatoirement (enigme et difficulte aleatoires)
48. La question propose 4 choix de reponses dont une seule bonne
49. Enigme difficile : 10 pieces d'or
50. Enigme moyenne : 10 pieces d'argent
51. Enigme facile : 10 pieces de bronze
52. Enigme difficile : perd 10 points de vie
53. Enigme moyenne : perd 6 points de vie
54. Enigme facile : perd 3 points de vie
55. Devenir "mage" apres avoir resolu 3 quetes de la categorie Magie

### Darquest : Augmenter ses PV (Priorite M)

56. Utiliser un sort ou une potion pour regagner des points de vie
57. Les PV rendus par un sort sont calcules selon son type (SpellDamage / 2, minimum 3)
58. Un sort utilise est supprime de l'inventaire ou sa quantite reduite de 1
59. Une potion peut donner au maximum 5 points de vie
60. Une potion utilisee est supprimee de l'inventaire ou sa quantite reduite de 1

### Darquest : Vendre un item (Priorite S)

61. Vendre un item de l'inventaire vers le magasin
62. Items normaux vendus a 60% de leur prix initial
63. Sorts vendus selon leur rarete : rarete 1 : 100%, rarete 2 : 95%, rarete 3 : 90%

### Enigma : Choisir la difficulte (Priorite S)

64. Choisir la difficulte de l'enigme (l'enigme reste aleatoire)
65. 3 enigmes difficiles reussies successivement : gagner 100 pieces d'or

### Enigma : Statistiques et Admin (Priorite C)

66. Afficher les statistiques du joueur : nombre de quetes reussies / total par difficulte
67. Ajouter des enigmes et leurs reponses dans la BD (interface admin)

### Tests Unitaires (Sprint 2)

68. Maintenir une batterie de tests unitaires pour le sprint en cours
69. Tests d'acceptation detailles sur les post-it (tenir compte des remarques du sprint zero)

---

## SPRINT 3

### Darquest : Compte et Securite

70. Valider le courriel d'un joueur a l'inscription (le courriel doit exister, DNS MX check)
71. Reinitialiser son mot de passe (email avec lien + token 1h)
72. Modifier le profil du joueur (alias, nom, email, avatar, mot de passe)
    - Changer d'email : reception d'un nouveau lien de verification

### Darquest : Evaluations

73. Evaluer et commenter un item achete (note de 1 a 5 avec demi-etoiles + commentaire)
74. Un joueur peut retirer son commentaire
75. Consulter les evaluations des items lors de la recherche d'item (etoiles sur la fiche)
76. Un admin peut retirer un commentaire d'un joueur
77. Afficher le pourcentage de personnes ayant evalue a chaque niveau d'etoiles (soigner l'affichage)

### Darquest : Admin

78. Ajouter des items dans la base de donnees (interface admin)
79. Augmenter le capital du joueur :
    - 1ere fois : 10 pieces d'or
    - 2eme fois : 10 pieces d'argent
    - 3eme fois : 10 pieces de bronze
80. Voir l'inventaire des joueurs (admin)

### Darquest : Gestion des PV

81. Fixer le nombre de points de vie maximum pour un joueur (interface admin pour modifier MaxHP)
82. Utilisation intelligente des potions/sorts : si le joueur a 95/100 PV, recommander une potion de 5 plutot qu'un sort de 50
83. Ne pas utiliser un sort ou une potion si le nombre maximum de points de vie est deja atteint

### Enigma : Nouveaux types d'enigme

84. Enigme avec reponse vrai ou faux
85. Enigme avec reponse par phrase courte

### Enigma : Statistiques graphique

86. Statistiques sous forme de graphique (Chart.js) dans le profil du joueur

### Tests Unitaires (Sprint 3)

87. Au moins 20 tests unitaires pour le sprint 3

---

## NON CLASSIFIABLES (processus / gestion de projet)

- Dettes du sprint 1 (corriger les remarques de la revue du sprint 1)
- Dettes du sprint 2
- Backlog du sprint 1 (modifie selon les fonctionnalites)
- Backlog du sprint 2
- Backlog du sprint 3 avec tableau id, Enonce, Test d'acceptation, P, E
- Stories priorisees et estimees (planning poker)
- Tests d'acceptation presents et bien ecrits
- Partager le Trello ou Jira avec le professeur
- Verifier la connexion au serveur PHP (chaque membre teste)
- Chaque membre doit tester qu'il peut transferer une page sur le serveur
- Creer et peupler la base de donnees (5 joueurs, 5 items par type)
- Configurer GitHub (comptes, depot, invitations)
- Creer le tableau de taches verticales (post-it au mur)
- Creer le Trello/Jira pour le sprint 1

---

## DISTRIBUTION DES ACTIONS PAR MEMBRE

### ABDOULLAH (22 actions) — Catalogue + Panier + Tests panier

Items 1, 2, 3, 4, 7, 8, 9, 10, 11, 12, 13, 14, 18, 34, 35, 36, 37, 38, 39, 40, 41, 42

| # | Description |
|---|-------------|
| 1 | Afficher tous les items avec les tris |
| 2 | Rechercher items selon un critere avec les tris |
| 3 | Rechercher items selon plusieurs criteres |
| 4 | Voir le detail d'un item |
| 7 | Ajouter un item au panier |
| 8 | Supprimer un item du panier |
| 9 | Modifier la quantite d'un item dans le panier |
| 10 | Consulter son panier |
| 11 | Payer son panier (solde + inventaire + stock + vider) |
| 12 | Payer sans avoir le montant |
| 13 | Acheter sans etre connecte |
| 14 | Acheter un sort sans etre mage |
| 18 | Conversion 1 or = 10 argent = 100 bronze |
| 34 | Test: Ajouter item au panier (affichage montant) |
| 35 | Test: Modifier la quantite (mise a jour montant) |
| 36 | Test: Supprimer item (mise a jour total) |
| 37 | Test: Ajouter avec quantite non disponible |
| 38 | Test: Modifier avec quantite non disponible |
| 39 | Test: Payer avec le montant suffisant |
| 40 | Test: Payer sans le montant |
| 41 | Test: Acheter sans se connecter |
| 42 | Test: Acheter un sort sans etre mage |

### NATHAN (22 actions) — Admin + Tests recherche + Securite + Responsive

Items 16, 26, 27, 28, 29, 30, 31, 32, 33, 46, 67, 68, 69, 70, 71, 75, 77, 78, 79, 80, 81, 87

| # | Description | Type |
|---|-------------|------|
| 16 | Prix d'un item : 1 a 100 pieces d'or | Regle metier |
| 26 | Test: Recherche sans aucun critere | Test |
| 27 | Test: Recherche avec tous les criteres | Test |
| 28 | Test: Recherche selon un seul critere | Test |
| 29 | Test: Recherche selon deux criteres | Test |
| 30 | Test: Recherche selon trois criteres | Test |
| 31 | Test: Afficher les details pertinents | Test |
| 32 | Test: Trier le resultat de la recherche | Test |
| 33 | Test: Interface conviviale | Test |
| 46 | Application adaptee pour cellulaire | Responsive |
| 67 | Ajouter des enigmes dans la BD (Admin) | **Admin** |
| 68 | Maintenir batterie de tests sprint 2 | Test |
| 69 | Tests d'acceptation detailles | Test |
| 70 | Valider le courriel a l'inscription | Securite |
| 71 | Reinitialiser son mot de passe | Securite |
| 75 | Consulter les evaluations des items | Affichage |
| 77 | Afficher le % d'evaluations par etoile | Affichage |
| 78 | Ajouter des items dans la BD (Admin) | **Admin** |
| 79 | Augmenter le capital du joueur (Admin) | **Admin** |
| 80 | Voir l'inventaire des joueurs (Admin) | **Admin** |
| 81 | Fixer le MaxHP d'un joueur (Admin) | **Admin** |
| 87 | 20 tests unitaires sprint 3 | Test |

### ISMAIL (21 actions) — Auth + Profil + Evaluations + Nouveaux types enigme + Graphiques

Items 5, 6, 15, 20, 21, 22, 23, 24, 25, 43, 44, 45, 66, 72, 73, 74, 76, 82, 83, 84, 85, 86

| # | Description |
|---|-------------|
| 5 | Inscription d'un joueur |
| 6 | Connexion d'un joueur |
| 15 | Consulter l'inventaire du joueur |
| 20 | Test: Validation de tous les champs |
| 21 | Test: L'alias est unique |
| 22 | Test: Le courriel est unique |
| 23 | Test: Afficher les cas d'erreur |
| 24 | Test: Connexion reussie (infos joueur) |
| 25 | Test: Connexion non reussie (message erreur) |
| 43 | Test: Aucun critere (inventaire) |
| 44 | Test: (Optionnel) Afficher selon un critere |
| 45 | Test: (Optionnel) Criteres combines |
| 66 | Afficher les stats du joueur dans Enigma |
| 72 | Modifier le profil du joueur |
| 73 | Evaluer et commenter un item achete |
| 74 | Un joueur retire son commentaire |
| 76 | Un admin retire un commentaire |
| 82 | Utilisation intelligente potions/sorts |
| 83 | Bloquer utilisation si max PV atteint |
| 84 | Enigme vrai ou faux |
| 85 | Enigme phrase courte |
| 86 | Statistiques sous forme de graphique |

### PHILIP (21 actions) — Enigmes + PV + Vente + Regles metier

Items 17, 19, 47, 48, 49, 50, 51, 52, 53, 54, 55, 56, 57, 58, 59, 60, 61, 62, 63, 64, 65

| # | Description |
|---|-------------|
| 17 | Montant initial 1000/1000/1000 |
| 19 | Seul un mage peut acheter un sort |
| 47 | Enigme aleatoire (enigme + difficulte) |
| 48 | 4 choix de reponses dont une bonne |
| 49 | Enigme difficile : 10 pieces d'or |
| 50 | Enigme moyenne : 10 pieces d'argent |
| 51 | Enigme facile : 10 pieces de bronze |
| 52 | Enigme difficile : perd 10 PV |
| 53 | Enigme moyenne : perd 6 PV |
| 54 | Enigme facile : perd 3 PV |
| 55 | Devenir mage (3 quetes magie) |
| 56 | Utiliser sort/potion pour regagner PV |
| 57 | PV rendus selon type de sort |
| 58 | Sort consomme ou quantite -1 |
| 59 | Potion max 5 PV |
| 60 | Potion consommee ou quantite -1 |
| 61 | Vendre un item vers le magasin |
| 62 | Items a 60% du prix initial |
| 63 | Sorts selon rarete (100%, 95%, 90%) |
| 64 | Choisir la difficulte de l'enigme |
| 65 | 3 difficiles successives : 100 or |
