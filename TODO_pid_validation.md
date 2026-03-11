# TODO: PID Prefix Validation for Edit Customer Data

## Task
Add validation that when user changes PID in "Ajukan Perubahan Data Pelanggan" menu, the new PID must match the prefix of the selected Branch (Cabang).

## Steps

- [x] 1. Add PID prefix validation in `ApprovalRequestController.php` - `storePelangganEditRequest` method
- [x] 2. Add PID prefix validation in `PelangganController.php` - `update` method (Super Admin direct update)
- [x] 3. Add Flash Messages display in `resources/views/layouts/main.blade.php`

## Implementation Details

### Logic:
- Get selected cabang_id from form
- Get the branch kode (e.g., "LX", "LZ")
- Get the PID from form
- Extract first 2 characters from PID
- Compare: PID prefix must match branch kode
- If not match, show error message

### Example error message:
"PID \"LX12345\" tidak sesuai dengan cabang \"Ciliwung\". Prefix PID harus \"LX\"."

