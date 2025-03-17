<?php
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/header.php';
?>

<div class="container mt-5">
    <h1 class="mb-4">Sell Your Account</h1>
    <p class="lead">Submit your account details and we'll review it for listing.</p>
    <div class="card">
        <div class="card-body">
            <form method="POST">
                <div class="mb-3">
                    <label for="game" class="form-label">Game</label>
                    <input type="text" class="form-control" id="game" name="game" required>
                </div>
                <div class="mb-3">
                    <label for="title" class="form-label">Account Title</label>
                    <input type="text" class="form-control" id="title" name="title" required>
                </div>
                <div class="mb-3">
                    <label for="description" class="form-label">Description</label>
                    <textarea class="form-control" id="description" name="description" rows="3" required></textarea>
                </div>
                <div class="mb-3">
                    <label for="price" class="form-label">Price ($)</label>
                    <input type="number" step="0.01" class="form-control" id="price" name="price" required>
                </div>
                <button type="submit" class="btn btn-primary">Submit</button>
            </form>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>