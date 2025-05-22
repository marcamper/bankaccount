<?php
declare(strict_types=1);

use Domain\BankAccount;
use Domain\Currency;
use Domain\Money;
use Domain\Payment;
use Domain\TransactionType;
use Infrastructure\Persistence\MySqlBankAccountRepository;

require_once __DIR__ . '/../vendor/autoload.php';

// Set HTTP response header for JSON responses
header('Content-Type: application/json; charset=utf-8');

// Global exception handler to return JSON error responses
set_exception_handler(function (Throwable $ex) {
    http_response_code(400);
    echo json_encode(['error' => $ex->getMessage()]);
    exit;
});

// Setup PDO connection
$pdo = new PDO(
    sprintf('mysql:host=%s;dbname=%s;charset=utf8mb4', getenv('DB_HOST'), getenv('DB_NAME')),
    getenv('DB_USER'),
    getenv('DB_PASS'),
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$repository = new MySqlBankAccountRepository($pdo);

// Basic router for demonstration
$method = $_SERVER['REQUEST_METHOD'];
$path = $_GET['action'] ?? '';

try {
    if ($method === 'POST' && $path === 'create-account') {
        $data = json_decode(file_get_contents('php://input'), true);
        $id = (int)$data['id'];
        $currency = new Currency($data['currency']);
        $account = new BankAccount($id, $currency);
        $repository->save($account);
        http_response_code(201);
        echo json_encode(['message' => 'Account created', 'id' => $id]);
        exit;
    }

    if ($method === 'POST' && $path === 'payment') {
        $data = json_decode(file_get_contents('php://input'), true);
        $account = $repository->getById((int)$data['account_id']);
        $money = new Money((string)$data['amount'], new Currency($data['currency']));
        $type = TransactionType::from($data['type']);
        $payment = new Payment($money, $type);

        if ($type === TransactionType::CREDIT) {
            $account->credit($payment);
        } else {
            $account->debit($payment);
        }

        $repository->save($account);

        echo json_encode(['message' => 'Payment processed']);
        exit;
    }

    if ($method === 'GET' && $path === 'balance') {
        $accountId = (int)($_GET['account_id'] ?? 0);
        $account = $repository->getById($accountId);
        $balance = $account->getBalance();
        echo json_encode([
            'account_id' => $accountId,
            'currency' => $balance->currency()->code(),
            'balance' => sprintf('%.2f', $balance->amount() / 100),
        ]);
        exit;
    }

    if ($method === 'GET' && $path === 'history') {
        $accountId = (int)($_GET['account_id'] ?? 0);
        $account = $repository->getById($accountId);
        $payments = array_map(fn(Payment $p) => [
            'type' => $p->type()->value,
            'amount' => sprintf('%.2f', $p->amount()->amount() / 100),
            'currency' => $p->amount()->currency()->code(),
            'date' => $p->date()->format('Y-m-d H:i:s'),
        ], $account->payments());
        echo json_encode(['payments' => $payments]);
        exit;
    }

    http_response_code(404);
    echo json_encode(['error' => 'Action not found']);

} catch (\Exception $ex) {
    // This catch is still here for safety, but most exceptions will be handled above by set_exception_handler
    http_response_code(400);
    echo json_encode(['error' => $ex->getMessage()]);
}