# 💬 MiniChat — Système de Chat avec Rooms + IA + Admin Panel  
Un mini-système de chat moderne développé en **PHP**, utilisant une base de données **PostgreSQL**, compatible avec **Render** et intégrant un assistant IA propulsé par **Groq Llama 3**.

## ✨ Fonctionnalités principales

### 🔐 Authenticité & Gestion des utilisateurs
- Inscription et connexion sécurisées
- Comptes uniques via pseudo
- Historique des connexions (IP + timestamp)
- Sessions PHP sécurisées

## 🏠 Rooms / Salons de discussion
- Créer un salon (avec ou sans mot de passe)
- Rejoindre un salon existant
- Supprimer un salon si créateur ou administrateur
- Messages regroupés par salon
- Rafraîchissement automatique des messages toutes les 2 secondes

## 🤖 Assistant IA intégré (Groq Llama 3)
- Sélection dynamique des modèles via API Groq
- Conversation en langage naturel
- Historique local minimal pour un contexte court
- Requêtes limitées pour éviter le spam
- Support de modèles récents :  
  - `llama-3.3-70b-versatile`  
  - `llama-3.1-8b-instant`

## 🔐 Panneau Administrateur (Admin Panel)
- Tableau de bord avec statistiques
- Nettoyer les messages
- Nettoyer l’historique de connexion
- Télécharger une sauvegarde SQL
- Sécurisé via mot de passe ADMIN_PASSWORD

## 🗄️ Base de données PostgreSQL
Structure :

```
users
rooms
messages
connect_history
```

## 🚀 Déploiement sur Render

### Variables d'environnement
| Nom | Description |
|-----|-------------|
| DATABASE_URL | PostgreSQL URL |
| GROQ_API_KEY | Clé API Groq |
| ADMIN_PASSWORD | Mot de passe admin |

### Commande de démarrage
```
php -S 0.0.0.0:10000
```

## 📂 Structure du projet

```
/minichat
│── index.html
│── login.php
│── inscription.php
│── chat.php
│── rooms.php
│── send_message.php
│── load_messages.php
│── ai.php
│── ai_proxy.php
│── admin.php
│── admin_login.php
│── admin_actions.php
│── init_db.php
│── fetch_models.php
│── styles.css
└── README.md
```

## 🙌 Auteur
Développé par **Liu Xuanshuo**
