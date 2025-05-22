CREATE TABLE IF NOT EXISTS bank_accounts (
                                             id INT PRIMARY KEY,
                                             currency VARCHAR(3) NOT NULL,
                                             created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS payments (
                                        id INT AUTO_INCREMENT PRIMARY KEY,
                                        account_id INT NOT NULL,
                                        amount_minor INT NOT NULL,
                                        currency VARCHAR(3) NOT NULL,
                                        type ENUM('credit', 'debit') NOT NULL,
                                        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                        CONSTRAINT fk_account FOREIGN KEY (account_id) REFERENCES bank_accounts(id) ON DELETE CASCADE
);