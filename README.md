# Bank Account DDD Sample Application

## Description

This project is a sample implementation of a bank account domain model with payments (credits and debits) following Domain-Driven Design principles.  
It includes:
- Domain entities and value objects in PHP 8.1,
- MySQL persistence layer,
- REST API,
- Minimal HTML frontend consuming the REST API,
- Unit tests with PHPUnit,
- Fully dockerized environment for easy setup on any platform.

---

## Technology stack

- PHP 8.1
- MySQL 8.0
- Docker + Docker Compose
- PHPUnit (for unit tests)

---

## Installing and running the project

### Prerequisites

- Install [Docker](https://www.docker.com/products/docker-desktop) and [Docker Compose](https://docs.docker.com/compose/install/) (Docker Desktop includes Compose).
- No need to install PHP or MySQL locally; all run in Docker containers.

### Steps

1. **Clone or download the repository**

```bash
git clone https://github.com/marcamper/bankaccount.git
cd bankaccount
```

2. **Build PHP/Apache container with application**
```bash 
   docker-compose up -d --build
   ```
This will:

Build PHP/Apache container with application,
Start MySQL container with initialized user, database, and password,
Mount source code for live edit (optional).
3. **Using provided init script:**

null.

Option B) Manual run (only if needed):

docker exec -it YOUR_DB_CONTAINER_NAME mysql -u user -ppassword bank < db/schema.sql
You can find the DB container name by:

docker ps
Accessing the application
Open browser at http://localhost:8080
This loads the minimal HTML frontend.
API endpoints (all requests to http://localhost:8080/api.php?action=...):
| Action | Method | Description | | ----------------- | ------ | --------------------------------------- | | create-account | POST | Create new account (id, currency) | | payment | POST | Make credit or debit payment on account | | balance | GET | Get current balance | | history | GET | Get payments history |

How to use the frontend
Fill the “Create Account” form (choose ID and currency).
Then use “Make Payment” form to credit or debit money.
View balance and payments history with provided buttons.
The frontend communicates with backend REST API.

Running unit tests
Enter the app container (replace container_id):
docker exec -it <container_id_or_name> bash
2.

./vendor/bin/phpunit tests/
All domain unit tests are located in tests/ directory.

Notes on payment saving implementation
Current approach
In the repository, when saving payments, all existing payments for a bank account are deleted and all current payments are re-inserted. This is a simplification done to speed up development and keep the code simple.

How it should be done professionally
Each payment should have a unique ID so new, updated, and deleted payments can be tracked.
The repository should perform incremental database operations:
Insert only new payments,
Update modified payments,
Delete removed payments,
This approach improves efficiency, concurrency safety, and maintainability.
ORMs like Doctrine can automate this with Unit of Work patterns.
Troubleshooting
If ports 8080 or 3306 are occupied, edit docker-compose.yml to change ports.
If the database doesn't initialize, ensure the volume is clean or remove dbdata volume:
docker-compose down -v
docker-compose up -d --build

## Contact
For questions, please contact: Marcin Brzeziński (marcamper@gmail.com)