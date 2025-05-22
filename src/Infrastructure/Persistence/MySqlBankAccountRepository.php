<?php
declare(strict_types=1);

namespace Infrastructure\Persistence;

use Domain\BankAccount;
use Domain\Currency;
use Domain\Payment;
use Domain\Money;
use Domain\TransactionType;
use Domain\CurrencyMismatchException;
use DateTimeImmutable;
use PDO;
use PDOException;

/**
 * Repository storing BankAccounts and Payments in MySQL database,
 * using PDO with transactions for atomicity.
 */
final class MySqlBankAccountRepository
{
    private PDO $pdo;

    public function __construct(PDO $pdo)
    {
        // Configure PDO for exceptions and proper mode
        $this->pdo = $pdo;
        $this->pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Saves new or updated BankAccount along with Payments.
     * Atomic operation within transaction.
     */
    public function save(BankAccount $account): void
    {
        $this->pdo->beginTransaction();

        try {
            $accountId = $account->id();

            // Check if account exists
            $stmt = $this->pdo->prepare('SELECT COUNT(*) FROM bank_accounts WHERE id = :id');
            $stmt->execute(['id' => $accountId]);
            $exists = (bool)$stmt->fetchColumn();

            if (!$exists) {
                // Insert new account
                $stmt = $this->pdo->prepare(
                    'INSERT INTO bank_accounts (id, currency, created_at) VALUES (:id, :currency, NOW())'
                );
                $stmt->execute([
                    'id' => $accountId,
                    'currency' => $account->currency()->code(),
                ]);
            }

            // Save payments
            // CURRENT SIMPLIFIED APPROACH:
            // To avoid duplicates and keep implementation simple in this demo/recruitment task,
            // all existing payments for the account are deleted from the database,
            // and then all payments currently held in the BankAccount entity
            // are re-inserted anew.
            //
            // This guarantees data consistency for this model without requiring change tracking,
            // but it is not efficient or scalable in real-world applications.
            // It also results in losing any database-assigned IDs (if payments had them),
            // and can cause issues with concurrent operations if not handled carefully.
            //
            // PRODUCTION-GRADE IMPLEMENTATION SHOULD:
            //
            // 1. Assign a unique identifier (e.g. UUID or auto-increment ID) to each Payment.
            //    That allows detection of new versus existing payments.
            //    - Payment entity/value object would have a unique ID property.
            //
            // 2. Implement change tracking in the domain or repository layer:
            //    - Track which payments are newly added, which remain unchanged,
            //      and which were removed from the BankAccount's payment collection.
            //
            // 3. In the repository's save() method:
            //    - Insert only new payments,
            //    - Update modified payments (if mutation is allowed),
            //    - Delete payments that were removed since last synchronization.
            //
            // 4. Use database transactions to ensure atomicity and avoid race conditions.
            //
            // 5. Optionally, use an ORM (like Doctrine) that supports Unit of Work patterns,
            //    automating tracking and persisting changes with minimal boilerplate.
            //
            // Benefits of this approach:
            // - Much better performance with large numbers of payments,
            // - Preserving Payment IDs allows referencing payments elsewhere,
            // - Cleaner concurrency and audit handling,
            // - Ability to extend payment model with more fields or update payments.
            //
            // SUMMARY:
            // The current "delete all and insert all" is acceptable for a small demo or prototype,
            // but production systems require careful tracking of entity states and incremental persistence.

            $deleteStmt = $this->pdo->prepare('DELETE FROM payments WHERE account_id = :account_id');
            $deleteStmt->execute(['account_id' => $accountId]);

            $insertStmt = $this->pdo->prepare(
                'INSERT INTO payments (account_id, amount_minor, currency, type, created_at) 
                 VALUES (:account_id, :amount_minor, :currency, :type, :created_at)'
            );

            foreach ($account->payments() as $payment) {
                $amount = $payment->amount();
                $insertStmt->execute([
                    'account_id' => $accountId,
                    'amount_minor' => $amount->amount(),
                    'currency' => $amount->currency()->code(),
                    'type' => $payment->type()->value,
                    'created_at' => $payment->date()->format('Y-m-d H:i:s'),
                ]);
            }

            $this->pdo->commit();

        } catch (PDOException $e) {
            $this->pdo->rollBack();
            throw $e;
        }
    }

    /**
     * Reconstruct BankAccount by id with payments.
     * @throws \RuntimeException if not found
     */
    public function getById(int $accountId): BankAccount
    {
        $stmt = $this->pdo->prepare('SELECT * FROM bank_accounts WHERE id = :id');
        $stmt->execute(['id' => $accountId]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$row) {
            throw new \RuntimeException("BankAccount with id $accountId not found");
        }

        $currency = new Currency($row['currency']);
        $account = new BankAccount($accountId, $currency);

        $paymentsStmt = $this->pdo->prepare('SELECT * FROM payments WHERE account_id = :account_id ORDER BY created_at ASC');
        $paymentsStmt->execute(['account_id' => $accountId]);

        while ($paymentRow = $paymentsStmt->fetch(PDO::FETCH_ASSOC)) {
            $paymentCurrency = new Currency($paymentRow['currency']);
            $money = Money::fromMinor((int)$paymentRow['amount_minor'], $paymentCurrency);
            $type = TransactionType::from($paymentRow['type']);
            $date = new DateTimeImmutable($paymentRow['created_at']);
            $payment = new Payment($money, $type, $date);

            if ($type === TransactionType::CREDIT) {
                $account->credit($payment);
            } else {
                $account->debit($payment);
            }
        }

        return $account;
    }
}