# Chapitre 3 — Analyse et Conception du Système (UML)

Ce document présente l'architecture logicielle, la conception de la base de données et la modélisation UML de la plateforme de réservation de transport. Il est conçu pour s'intégrer directement dans la documentation technique ou le rapport de PFE (Projet de Fin d'Études).

## 3.1 Diagramme de Cas d'Utilisation (Use Case Diagram)

Le diagramme des cas d'utilisation modélise les interactions entre les acteurs (Client, Transporteur, Administrateur) et le système.

**Acteurs identifiés :**
- **Client (Expéditeur)** : Utilisateur souhaitant faire transporter une cargaison.
- **Transporteur (Chauffeur)** : Professionnel possédant des véhicules et proposant ses services.
- **Administrateur** : Superviseur de la plateforme garantissant le bon fonctionnement et gérant les commissions.

### 3.1.1 Diagramme Général des Cas d'Utilisation
Ce diagramme offre une vue d'ensemble des interactions entre les acteurs et les grands blocs fonctionnels du système.

```plantuml
@startuml
left to right direction
actor "Client" as C
actor "Transporteur" as T
actor "Administrateur" as A

rectangle "Système de Réservation de Transport" {
  (S'authentifier) as UC_Auth
  (Gérer son profil) as UC_Profile
  
  C -- (UC_Auth)
  C -- (UC_Profile)
  T -- (UC_Auth)
  T -- (UC_Profile)
  A -- (UC_Auth)
  
  (Gérer les Réservations) as UC_Res
  (Gérer les Véhicules) as UC_Veh
  (Suivre les Revenus/Paiements) as UC_Money
  (Administration Système) as UC_Admin
  
  C -- UC_Res
  T -- UC_Res
  T -- UC_Veh
  T -- UC_Money
  A -- UC_Res
  A -- UC_Veh
  A -- UC_Admin
}
@enduml
```

### 3.1.2 Cas d'Utilisation - Client
Focus sur les fonctionnalités dédiées à l'expéditeur.

```plantuml
@startuml
left to right direction
actor "Client" as C

rectangle "Module Client" {
  (Publier une demande de transport) as UC1
  (Annuler une demande pendante) as UC2
  (Suivre l'état d'un trajet en temps réel) as UC3
  (Consulter l'historique des trajets) as UC4
  (Négocier le prix) as UC5
  
  C -- UC1
  C -- UC2
  C -- UC3
  C -- UC4
  (UC1) <.. (UC5) : <<extend>>
}
@enduml
```

### 3.1.3 Cas d'Utilisation - Transporteur
Focus sur la gestion de la flotte et des missions.

```plantuml
@startuml
left to right direction
actor "Transporteur" as T

rectangle "Module Transporteur" {
  (Gérer les véhicules de la flotte) as UC1
  (Consulter les demandes disponibles) as UC2
  (Accepter une demande) as UC3
  (Proposer un prix / Négocier) as UC4
  (Mettre à jour le statut du job) as UC5
  (Consulter les gains et statistiques) as UC6
  
  T -- UC1
  T -- UC2
  T -- UC3
  T -- UC5
  T -- UC6
  (UC3) <.. (UC4) : <<extend>>
}
@enduml
```

### 3.1.4 Cas d'Utilisation - Administrateur
Focus sur la modération et l'audit.

```plantuml
@startuml
left to right direction
actor "Administrateur" as A

rectangle "Module Administration" {
  (Vérifier et Valider les comptes) as UC1
  (Approuver / Désactiver des véhicules) as UC2
  (Surveiller les flux de réservations) as UC3
  (Consulter les journaux d'audit (Logs)) as UC4
  (Gérer les commissions) as UC5
  (Gérer les litiges) as UC6
  
  A -- UC1
  A -- UC2
  A -- UC3
  A -- UC4
  A -- UC5
}
@enduml
```

---

## 3.2 Diagramme de Classes (Class Diagram)

Ce diagramme illustre la structure des données du système, en correspondance avec la base de données relationnelle (MySQL InnoDB). Il met en évidence les entités principales et leurs relations.

```mermaid
classDiagram
    class User {
        +int id
        +string name
        +string email
        +string password_hash
        +string phone
        +enum role (client, transporter, admin)
        +enum status (active, suspended, pending)
        +string id_card_url
        +bool id_is_verified
        +bool contract_signed
        +datetime created_at
    }

    class Vehicle {
        +int id
        +int owner_id
        +string vehicle_type
        +decimal capacity
        +string plate_number
        +enum status (active, inactive)
        +bool is_activation_requested
        +datetime created_at
    }

    class Reservation {
        +int id
        +int client_id
        +int vehicle_id
        +string pickup_location
        +string destination
        +string cargo_type
        +decimal weight
        +decimal volume
        +datetime reservation_date
        +decimal price
        +enum price_type (fixed, negotiable)
        +decimal transporter_proposed_price
        +enum status (pending, negotiation, accepted, in_progress, completed, cancelled)
        +datetime created_at
    }

    class Notification {
        +int id
        +int user_id
        +string message
        +enum status (unread, read)
        +datetime created_at
    }

    class Earnings {
        +int id
        +int transporter_id
        +int reservation_id
        +decimal amount
        +datetime created_at
    }
    
    class ReservationLog {
        +int id
        +int reservation_id
        +string old_status
        +string new_status
        +int changed_by
        +datetime created_at
    }

    User "1" -- "*" Vehicle : "Possède"
    User "1" -- "*" Reservation : "Demande (Client)"
    User "1" -- "*" Notification : "Reçoit"
    User "1" -- "*" Earnings : "Encaisse (Transporteur)"
    Vehicle "1" -- "*" Reservation : "Assigné à"
    Reservation "1" -- "1" Earnings : "Génère"
    Reservation "1" -- "*" ReservationLog : "Audit trail"
```

---

## 3.3 Diagrammes de Séquence (Sequence Diagrams)

### 3.3.1 Inscription d'un Utilisateur
Le processus d'inscription montre comment les données sont sécurisées, hachées, puis insérées dans la base de données avant la redirection vers la page d'attente.

```mermaid
sequenceDiagram
    actor U as Utilisateur
    participant F as Frontend (register.php)
    participant B as Backend (functions/auth.php)
    participant DB as Database (MySQL)

    U->>F: Remplit le formulaire (Nom, Email, MDP, Rôle)
    F->>B: POST request
    B->>B: Validation & password_hash()
    B->>DB: INSERT INTO users (status='pending')
    DB-->>B: Retourne lastInsertId()
    B->>B: Initialise la session (safe_session_start)
    B-->>F: Redirection system/pending.php
    F-->>U: Affiche la page "En attente d'approbation"
```

### 3.3.2 Création d'une Demande de Transport
Détaille les interactions de sécurité (Middleware) et la transaction lors de l'enregistrement d'une demande par un client.

```mermaid
sequenceDiagram
    actor C as Client
    participant UI as Formulaire (request_transport.php)
    participant MW as Middleware (auth_check.php)
    participant B as Backend (lifecycle.php)
    participant DB as Database

    C->>UI: Saisit les détails (pickup, destination, cargo...)
    UI->>MW: POST request (CSRF token inclus)
    MW->>MW: Vérification session & RBAC (Client)
    MW->>B: Transfert des données
    B->>DB: BEGIN Transaction
    B->>DB: INSERT INTO reservations (status='pending')
    DB-->>B: reservation_id
    B->>DB: INSERT INTO reservation_logs
    B->>DB: COMMIT
    B-->>UI: Succès (Toast / Redirection)
    UI-->>C: Affiche la réservation dans "Mes Réservations"
```

### 3.3.3 Acceptation d'une Demande par le Transporteur
Ce diagramme est critique, car il inclut la gestion de la concurrence (`SELECT ... FOR UPDATE`) empêchant deux transporteurs d'accepter la même mission.

```mermaid
sequenceDiagram
    actor T as Transporteur
    participant UI as Dashboard (requests.php)
    participant B as Backend (lifecycle.php)
    participant DB as Database

    T->>UI: Clique sur "Accepter la demande"
    UI->>B: POST action=accept, reservation_id, vehicle_id
    B->>DB: BEGIN Transaction
    B->>DB: SELECT status FROM reservations WHERE id=? FOR UPDATE
    DB-->>B: status == 'pending'
    B->>DB: UPDATE reservations SET vehicle_id=?, status='accepted'
    B->>DB: INSERT INTO reservation_logs
    B->>DB: INSERT INTO notifications (Notify Client)
    B->>DB: COMMIT
    B-->>UI: Retour succès
    UI-->>T: Met à jour l'interface utilisateur

### 3.3.4 Processus de Négociation du Prix
Montre l'échange entre le transporteur (proposition) et le client (acceptation).

```mermaid
sequenceDiagram
    actor T as Transporteur
    actor C as Client
    participant B as Backend (lifecycle.php)
    participant DB as Databasess

    T->>B: Propose un nouveau prix (negotiated_price)
    B->>DB: UPDATE status='negotiation', transporter_proposed_price=?
    B->>DB: INSERT INTO reservation_logs
    B->>DB: INSERT INTO notifications (Pour le Client)
    DB-->>C: Notification: "Nouvelle offre reçue"
    
    C->>B: Accepte l'offre du transporteur
    B->>DB: UPDATE status='accepted', price=transporter_proposed_price
    B->>DB: INSERT INTO reservation_logs
    B->>DB: COMMIT
    B-->>C: Succès
```
```

---

## 3.4 Diagramme d'Activité (Activity Diagram)

Ce diagramme représente le cycle de vie métier d'une réservation, depuis sa création jusqu'à sa finalisation, en respectant les états stricts définis dans la base de données.

```mermaid
stateDiagram-v2
    [*] --> Pending : Créé par le Client
    
    Pending --> Cancelled : Annulé par le Client
    Pending --> Accepted : Accepté par le Transporteur
    
    Accepted --> In_Progress : Marchandise récupérée (En route)
    Accepted --> Rejected : Annulé par le Transporteur (Exception)
    
    In_Progress --> Completed : Livraison confirmée
    
    Completed --> [*] : Génération des revenus / commissions
    Cancelled --> [*]
    Rejected --> [*]
```

---

## 3.5 Diagramme de Composants (Component Diagram)

Ce diagramme montre l'architecture modulaire du projet, en séparant la présentation de la logique métier et des données.

```plantuml
@startuml
package "Frontend (Client-Side)" {
  [Navigateur Web] as Browser
  [HTML / CSS (Glassmorphism) / JS] as UI
}

package "Backend (Server-Side / PHP)" {
  [Middleware de Sécurité\n(auth_check, role_gate)] as Middleware
  [Pages / Vues\n(client, transporter, admin)] as Views
  [Logique Métier\n(functions/)] as Logic
  [Configuration & DB\n(config/database.php)] as Config
}

package "Couche de Données" {
  database "MySQL InnoDB" as DB
}

Browser --> UI : HTTP/HTTPS
UI --> Middleware : Requêtes GET/POST
Middleware --> Views : Route autorisée
Views --> Logic : Appels de fonctions
Logic --> Config : Connexion PDO
Config --> DB : Requêtes SQL sécurisées
@enduml
```

---

## 3.6 Diagramme de Déploiement (Deployment Diagram)

Ce diagramme modélise l'architecture physique ou virtuelle du système une fois mis en production, indiquant les protocoles de communication.

```plantuml
@startuml
node "Dispositif Client (PC, Smartphone)" as ClientDevice {
  component "Navigateur Web\n(Chrome, Safari, Firefox)" as WebBrowser
}

node "Serveur Web (Apache / Nginx)" as WebServer {
  component "Moteur PHP 8+" as PHPApp
  folder "Code Source" {
    file "includes/"
    file "functions/"
    file "core/config/"
  }
}

node "Serveur de Base de Données" as DBServer {
  database "MySQL / MariaDB" as MySQLDB
}

ClientDevice -- WebServer : "HTTPS (Port 443)"
WebServer -- DBServer : "TCP/IP (Port 3306)"
@enduml
```

---
*Ce document respecte fidèlement les implémentations et les choix technologiques du projet (PHP, PDO, requêtes préparées, transactions SQL, RBAC).*
