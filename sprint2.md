```
SALIHA YACOUB, LUC LEDOUX 1
```

```
420 - KBE-LG - Projet dirigé
```

# Darquest, détails d’implémentation du sprint 2 : gagner des pièces.

- Date début : 31 mars (selon le groupe)
- Date de revue (démonstration) : 21 avril (selon le groupe)

Voici la liste des fonctionnalités attendues pour le sprint 2. Enigma est une application Web
adaptative.(adaptée pour un cellulaire)

- Une énigme a 4 choix de réponses dont une seule bonne réponse. C’est une contrainte à
  respecter absolument. Concept plus simple à implémenter.

```
Énoncé des fonctionnalité Priorité
Dettes du sprint 1, (tenir compte des remarques lors de la revue du sprint 1) M
Enigma, gagner des pièces : Résoudre une énigme de manière aléatoire. L’énigme
est tirée de manière aléatoire, la difficulté est aléatoire.
```

- Énigme difficile →10 pièces d’or
- Énigme moyen → 10 pièces d’argent
- Énigme facile →10 pièces de bronze

## M

```
Perte des points de vie : (nouvelle story)
Lorsqu’un joueur ne donne pas la bonne réponse à une énigme il perd des points de
vie :
```

- Énigme facile, il perd 3 pts de vie
- Énigme moyenne, il perd 6 pts de vie
- Énigme difficile, il perd 10 pts de vie

## M

```
Darquest, augmenter ses points de vie en utilisant des sorts ou des potions
(nouvelle story)
Le joueur peut utiliser les sorts pour reprendre des points de vie. Les points de vie à
reprendre sont en fonction du type de sort. Un sort utilisé est soit supprimé de
l’inventaire, soit sa quantité est réduite de 1.
Une potion peut donner au maximum 5 points de vie. Idem pour la potion. (Une
potion utilisée est soit supprimée de l’inventaire, soit sa quantité est réduite de 1.)
```

## M

```
Enigma : Devenir « mage »
Lorsqu’un joueur a résolu 3 quêtes en rapport avec la magie (Sorts), il devient mage.
.
```

## M

```
Darquest, vendre un item
Pour gagner des pièces d’or, le joueur peut vendre un item. Les items sont vendus
60 % de leur valeur initiale, sauf pour les sorts qui sont vendus selon leur rareté :
rareté 1 : → 100 % du prix initial
rareté 2 : → 95 % du prix initial
rareté 3 :→ 90 % du prix initial
La vente se fera vers le magasin
```

## S

```
Enigma, gagner encore plus pièces Résoudre une énigme en choisissant la difficulté.
Le joueur peut choisir la difficulté, l’énigme est aléatoire.
```

- Énigme difficile →10 pièces d’or
- Énigme moyen →10 pièces d’argent
- Énigme facile →10 pièces de bronze

## S

```
SALIHA YACOUB, LUC LEDOUX 2
```

```
420 - KBE-LG - Projet dirigé
```

- **Lorsque le joueur a répondu successivement à 3 énigmes difficiles il gagne**
  **100 pièces d’or (nouveau)**

```
Enigma : Afficher les statistique du joueurs dans Enigma. Nombre de quêtes
réussies/nombre de quêtes totales selon la difficulté de la quête. :
```

## C

```
Enigma : Ajouter des énigmes et leurs réponses dans la BD (admin). (nouvelle
story).
```

## C

Vous devez faire un backlog de sprint 2 à partir de la liste des fonctionnalités plus haut. La date
de remise du backlog du sprint 2 est la même journée que le début du sprint 2. Le backlog
pourrait être vu comme la liste des post-it au mur.

Pour les tests d’acceptation, ils doivent être détaillés. Vous devez tenir compte des remarques
faîtes lors du sprint zéro. Ces tests doivent-être à l’endo de la post-it

Vous devez maintenir une batterie de tests unitaires pour le sprint en cours.
