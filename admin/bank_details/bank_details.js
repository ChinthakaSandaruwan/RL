// Bank Details Management JavaScript

function editAccount(account) {
    document.getElementById('edit_account_id').value = account.account_id;
    document.getElementById('edit_bank_id').value = account.bank_id;
    document.getElementById('edit_branch').value = account.branch;
    document.getElementById('edit_account_number').value = account.account_number;
    document.getElementById('edit_holder_name').value = account.account_holder_name;

    const modal = new bootstrap.Modal(document.getElementById('editAccountModal'));
    modal.show();
}

function deleteBank(bankId) {
    if (confirm('Are you sure you want to delete this bank? You cannot delete a bank with existing accounts.')) {
        document.getElementById('delete_bank_id').value = bankId;
        document.getElementById('deleteBankForm').submit();
    }
}

function deleteAccount(accountId) {
    if (confirm('Are you sure you want to delete this bank account? This action cannot be undone.')) {
        document.getElementById('delete_account_id').value = accountId;
        document.getElementById('deleteAccountForm').submit();
    }
}
