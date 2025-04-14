## Installation sur Docker ou Wamp

##  Fonctions de sécurité 

- `require_login()` | Accorde l'accès aux pages pour les utilisateurs connectés 
- `is_admin()` / `is_prof()` | Contrôle des rôles (accès restreint) 
- `csrf_protection` | Vérifie la présence du token 
- `logs_written` | Présence de journaux de sécurité (`logs/security.log`) 
- `password_hash()` | Hachage des mots de passe avant insertion 
- `strip_tags()` | Rejet des balises HTML dans les champs 
- `duplicate_check` | Vérifie l’existence d’un utilisateur avant création (`SELECT` + `fetch`) 

##  Bonnes pratiques 

- `dashboard.php` est protégé (`require_login()` et `is_admin()`).
- Il affiche  les **logs récents** via lecture de `logs/security.log`.
- Les mots de passe sont bien hachés (`password_hash()`).
- Plusieurs pages incluent `session_start()` et du CSRF.
- `logout.php` écrit dans les logs.

##  Remarques

- Le fichier `auth.php` centralise l’accès : `require_login()` est appelé dans chaque page via `auth.php`
- Le fichier `logs/security.log` est utilisé dans plusieurs scripts, et **affiché dans `dashboard.php`** 
- Aucune tentative d’accès direct à la base sans vérification des droits.
