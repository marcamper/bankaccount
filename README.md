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


## Accessing the application
Open browser at http://localhost:8080

This loads the minimal HTML frontend.

API endpoints (all requests to http://localhost:8080/api.php?action=...):

| Action          | Method | Description                              |
|-----------------|--------|------------------------------------------|
| create-account  | POST   | Create new account (id, currency)        |
| payment         | POST   | Make credit or debit payment on account  |
| balance         | GET    | Get current balance                       |
| history         | GET    | Get payments history                      |


## How to use the frontend
Fill the “Create Account” form (choose ID and currency).
Then use “Make Payment” form to credit or debit money.
View balance and payments history with provided buttons.
The frontend communicates with backend REST API.

## Running unit tests
Enter the app container:
``` bash
docker exec -it bankaccount-app bash
```

2. All domain unit tests are located in tests/ directory.
``` bash
./vendor/bin/phpunit tests/
```

## Notes on payment saving implementation
### Current approach
To avoid duplicates and keep implementation simple in this demo/recruitment task, all existing payments for the account are deleted from the database, and then all payments currently held in the BankAccount entity are re-inserted a new.
## Production-grade implementation should:
1. Assign a unique identifier (e.g. UUID or auto-increment ID) to each Payment.
   That allows detection of new versus existing payments.
   - Payment entity/value object would have a unique ID property.

2. Implement change tracking in the domain or repository layer:
   - Track which payments are newly added, which remain unchanged,
   and which were removed from the BankAccount's payment collection.

3. In the repository's save() method:
   - Insert only new payments,
   - Update modified payments (if mutation is allowed),
   - Delete payments that were removed since last synchronization.

4. Use database transactions to ensure atomicity and avoid race conditions.

5. Optionally, use an ORM (like Doctrine) that supports Unit of Work patterns,
   automating tracking and persisting changes with minimal boilerplate.

### Benefits of this approach:
   - Much better performance with large numbers of payments,
   - Preserving Payment IDs allows referencing payments elsewhere,
   - Cleaner concurrency and audit handling,
   - Ability to extend payment model with more fields or update payments.

### SUMMARY:
The current "delete all and insert all" is acceptable for a small demo or prototype,
but production systems require careful tracking of entity states and incremental persistence.

## Contact
For questions, please contact: Marcin Brzeziński (marcamper@gmail.com)