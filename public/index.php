<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1" />
    <title>Bank Account Management</title>
    <style>
        body {font-family: Arial, sans-serif; margin: 20px;}
        fieldset {margin-bottom: 20px; padding: 10px;}
        label {display: block; margin-top: 8px;}
        input, select, button {padding: 6px; margin-top: 4px; min-width: 200px;}
        #messages {margin-top: 15px; color: red;}
        table {border-collapse: collapse; width: 100%; margin-top: 10px;}
        th, td {border: 1px solid #ccc; padding: 8px; text-align:left;}
    </style>
</head>
<body>

<h1>Bank Account Management</h1>

<fieldset>
    <legend>Create New Account</legend>
    <label for="accountId">Account ID (integer):</label>
    <input type="number" id="accountId" required />
    <label for="accountCurrency">Currency:</label>
    <select id="accountCurrency" required>
        <option value="">-- select --</option>
        <option value="PLN">PLN</option>
        <option value="USD">USD</option>
        <option value="EUR">EUR</option>
    </select>
    <br />
    <button onclick="createAccount()">Create Account</button>
</fieldset>

<fieldset>
    <legend>Make Payment (Credit or Debit)</legend>
    <label for="paymentAccountId">Account ID:</label>
    <input type="number" id="paymentAccountId" required />
    <label for="paymentType">Type:</label>
    <select id="paymentType" required>
        <option value="">-- select --</option>
        <option value="credit">Credit (Deposit)</option>
        <option value="debit">Debit (Withdrawal)</option>
    </select>
    <label for="paymentAmount">Amount (with two decimals):</label>
    <input type="number" id="paymentAmount" step="0.01" min="0" required />
    <label for="paymentCurrency">Currency:</label>
    <select id="paymentCurrency" required>
        <option value="">-- select --</option>
        <option value="PLN">PLN</option>
        <option value="USD">USD</option>
        <option value="EUR">EUR</option>
    </select>
    <br />
    <button onclick="makePayment()">Submit Payment</button>
</fieldset>

<fieldset>
    <legend>Account Info</legend>
    <label for="infoAccountId">Account ID:</label>
    <input type="number" id="infoAccountId" />
    <br />
    <button onclick="getBalance()">Get Balance</button>
    <button onclick="getHistory()">Get Payment History</button>

    <div id="balanceResult"></div>
    <div id="historyResult"></div>
</fieldset>

<div id="messages"></div>

<script>
    const apiUrl = 'api.php';

    function showMessage(msg, isError = true) {
        const el = document.getElementById('messages');
        el.style.color = isError ? 'red' : 'green';
        el.textContent = msg;
    }

    async function createAccount() {
        const id = document.getElementById('accountId').value.trim();
        const currency = document.getElementById('accountCurrency').value;
        if (!id || !currency) {
            showMessage('Account ID and Currency are required');
            return;
        }

        try {
            const res = await fetch(`${apiUrl}?action=create-account`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({id: Number(id), currency})
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Failed to create account');
            showMessage('Account created successfully', false);
        } catch (e) {
            showMessage(e.message);
        }
    }

    async function makePayment() {
        const accountId = document.getElementById('paymentAccountId').value.trim();
        const type = document.getElementById('paymentType').value;
        const amount = document.getElementById('paymentAmount').value;
        const currency = document.getElementById('paymentCurrency').value;

        if (!accountId || !type || !amount || !currency) {
            showMessage('All payment fields are required');
            return;
        }

        if (Number(amount) <= 0) {
            showMessage('Amount must be positive');
            return;
        }

        try {
            const res = await fetch(`${apiUrl}?action=payment`, {
                method: 'POST',
                headers: {'Content-Type': 'application/json'},
                body: JSON.stringify({
                    account_id: Number(accountId),
                    type,
                    amount,
                    currency
                })
            });
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Failed to make payment');
            showMessage('Payment processed successfully', false);
        } catch (e) {
            showMessage(e.message);
        }
    }

    async function getBalance() {
        const id = document.getElementById('infoAccountId').value.trim();
        if (!id) {
            showMessage('Enter Account ID');
            return;
        }
        try {
            const res = await fetch(`${apiUrl}?action=balance&account_id=${encodeURIComponent(id)}`);
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Failed to get balance');
            document.getElementById('balanceResult').textContent =
                `Balance: ${json.balance} ${json.currency}`;
            showMessage('', false);
        } catch (e) {
            showMessage(e.message);
        }
    }

    async function getHistory() {
        const id = document.getElementById('infoAccountId').value.trim();
        if (!id) {
            showMessage('Enter Account ID');
            return;
        }
        try {
            const res = await fetch(`${apiUrl}?action=history&account_id=${encodeURIComponent(id)}`);
            const json = await res.json();
            if (!res.ok) throw new Error(json.error || 'Failed to get history');

            if (json.payments.length === 0) {
                document.getElementById('historyResult').textContent = 'No payments found.';
                return;
            }

            const rows = json.payments.map(p => `
            <tr>
                <td>${p.type}</td>
                <td>${p.amount} ${p.currency}</td>
                <td>${p.date}</td>
            </tr>
        `).join('');

            document.getElementById('historyResult').innerHTML = `
            <table>
                <thead>
                    <tr><th>Type</th><th>Amount</th><th>Date</th></tr>
                </thead>
                <tbody>${rows}</tbody>
            </table>`;
            showMessage('', false);
        } catch (e) {
            showMessage(e.message);
        }
    }
</script>

</body>
</html>