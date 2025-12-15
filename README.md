# 💬 MiniChat — Système de Chat avec Rooms + IA + Admin Panel
Un mini-système de chat moderne développé en **PHP** avec une base **PostgreSQL**, pensé pour Render et intégrant un assistant IA propulsé par **Groq**.

## ✨ Fonctionnalités principales

### 🔐 Authentification & sécurité
- Inscription et connexion avec mots de passe hashés ; les anciens comptes sont automatiquement migrés vers un hash sécurisé lors de la connexion.【F:login.php†L52-L81】
- Sessions PHP pour protéger l'accès au chat, aux salons et à l'assistant IA.【F:chat.php†L1-L17】【F:ai.php†L1-L7】
- Historique des connexions (IP + timestamp) pour chaque connexion réussie.【F:login.php†L55-L68】

### 🏠 Salons de discussion
- Création de salons publics ou protégés par mot de passe (hashé en base).【F:rooms.php†L79-L132】
- Rejoindre un salon existant, avec contrôle de mot de passe si nécessaire.【F:rooms.php†L91-L118】【F:rooms.php†L226-L246】
- Suppression d'un salon par son créateur ou par l'administrateur ; nettoyage automatique des messages liés.【F:rooms.php†L134-L170】
- Sélection d'un salon obligatoire avant d'accéder au chat.【F:chat.php†L9-L21】

### 💬 Chat en temps réel léger
- Affichage des 20 derniers messages du salon courant, rafraîchis automatiquement toutes les 2 secondes via `load_messages.php`.【F:chat.php†L37-L78】【F:chat.php†L110-L135】
- Envoi de messages persistés en base par `send_message.php`.【F:send_message.php†L31-L65】
- Mentions `@ai` ou `@bot` déclenchant une réponse automatique d'**AI_BOT** (Groq), insérée comme un message normal dans le salon.【F:send_message.php†L69-L126】

### 🤖 Assistant IA dédié
- Page dédiée à l'IA avec historique local minimal et interface moderne.【F:ai.php†L16-L76】【F:ai.php†L90-L169】
- Chargement dynamique de la liste des modèles Groq via `fetch_models.php`, avec mise en cache côté serveur dans `ai_proxy.php` pour limiter les appels externes.【F:ai.php†L86-L119】【F:ai_proxy.php†L24-L57】
- Limitation anti-spam de 10 requêtes IA par minute et tronquage de l'historique envoyé (20 derniers messages).【F:ai_proxy.php†L11-L22】【F:ai_proxy.php†L67-L84】
- Sélection d'un modèle côté client avec repli automatique vers `llama-3.3-70b-versatile` si le modèle demandé n'est pas disponible.【F:ai.php†L100-L117】【F:ai_proxy.php†L59-L66】

### 🔐 Panneau Administrateur
- Authentification dédiée via `ADMIN_PASSWORD` pour accéder au tableau de bord.【F:admin_verify.php†L1-L15】
- Statistiques globales (utilisateurs, salons, messages, connexions) et liste des dernières IP par utilisateur.【F:admin.php†L8-L53】【F:admin.php†L55-L90】
- Actions rapides : vider les messages, effacer l'historique de connexions, supprimer un salon ou un utilisateur, générer une sauvegarde SQL exportable.【F:admin.php†L98-L163】【F:admin_actions.php†L27-L102】【F:admin_actions.php†L121-L167】

## 🗄️ Base de données PostgreSQL
Tables utilisées :
- `users` — comptes (pseudo unique, mot de passe hashé).【F:inscription.php†L33-L53】
- `rooms` — salons avec créateur, date de création et mot de passe optionnel hashé.【F:rooms.php†L81-L125】
- `messages` — messages liés à un salon et à un pseudo, timestamps automatiques.【F:chat.php†L37-L78】【F:send_message.php†L55-L86】
- `connect_history` — journalisation des connexions (pseudo, IP, date).【F:login.php†L55-L68】

Initialisation : exécuter `init_db.php` ou appliquer `create_table_miniChat.sql`/`init_minichat.sql` pour créer les tables avant utilisation.

## 🚀 Déploiement & exécution

### 🌐 Démo en ligne
- Instance publique : https://chat.liuxs.my

### Variables d'environnement
| Nom | Description |
|-----|-------------|
| `DATABASE_URL` | URL PostgreSQL (format Render) |
| `GROQ_API_KEY` | Clé API Groq pour l'IA |
| `ADMIN_PASSWORD` | Mot de passe du panneau admin |

### Lancer en local
```bash
php -S 0.0.0.0:10000
```
Puis ouvrir `http://localhost:10000/index.html` pour accéder à la page de connexion.

## 📂 Structure du projet
```text
/minichat
├── index.html               # Connexion
├── inscription.html/.php    # Création de compte
├── login.php / logout.php   # Authentification
├── rooms.php                # Gestion des salons
├── chat.php                 # Interface de chat + auto-refresh
├── send_message.php         # Envoi + bot IA dans le salon
├── load_messages.php        # Récupération des messages (polling)
├── ai.php / ai_proxy.php    # Assistant IA dédié
├── fetch_models.php         # Liste dynamique des modèles Groq
├── admin_login.php          # Authentification admin
├── admin.php / admin_actions.php / admin_verify.php
├── init_db.php              # Création des tables
├── styles.css               # UI
└── README.md
```

## 🙌 Auteur
Développé par **Liu Xuanshuo**
