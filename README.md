# WP AI Info

**Version :** 1.0.0  
**Auteur :** Gilles Dumas  
**Site de l’auteur :** [https://gillesdumas.com](https://gillesdumas.com)

## Description

WP AI Info est un plugin WordPress permettant de créer et publier automatiquement des articles de blog en s’appuyant sur une IA (OpenAI). Il génère le contenu en français et l’insère directement en tant qu’article « publié » dans WordPress.

## Fonctionnalités

- **Création d’articles automatiques** à partir de l’API OpenAI.
- **Formatage du texte** avec Markdown (conversion en HTML via [Parsedown](https://github.com/erusev/parsedown)).
- **Programmation possible** via un simple appel GET (ex : `?inserer-article-ai`).
- **Gestion de la locale** française pour la date et le contenu.

## Installation

1. **Télécharger** ou cloner ce dépôt dans un répertoire nommé `wp-ai-info`.
2. **Placer** le dossier dans le répertoire `wp-content/plugins/` de votre installation WordPress.
3. **Installer les dépendances** avec Composer :
   ```bash
   composer install
   composer require nesbot/carbon
   composer require erusev/parsedown