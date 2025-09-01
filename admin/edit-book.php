<?php
session_start();

// Check admin access
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../db_config.php';

$bookId = $_GET['id'] ?? 0;
$message = '';
$error = '';

// Handle book update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    
    // VULNERABILITY: SQL Injection
    $updateSql = "UPDATE Products SET 
                  Title = '$title',
                  Author = '$author',
                  Description = '$description',
                  Price = $price,
                  StockQuantity = $stock,
                  CategoryID = $category
                  WHERE ProductID = $bookId";
    
    if (sqlsrv_query($conn, $updateSql)) {
        $message = "Book updated successfully!";
        
        // Log action
        $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput) 
                  VALUES (" . $_SESSION['admin_id'] . ", 'Book Updated', 'Products', 'Updated book: $title')";
        sqlsrv_query($conn, $logSql);
    } else {
        $error = "Failed to update book.";
    }
}

// Get book details
$bookSql = "SELECT * FROM Products WHERE ProductID = $bookId";
$bookStmt = sqlsrv_query($conn, $bookSql);
$book = sqlsrv_fetch_array($bookStmt, SQLSRV_FETCH_ASSOC);

if (!$book) {
    header('Location: manage-books.php');
    exit;
}

// Get categories for dropdown
$categoriesSql = "SELECT * FROM Categories";
$categoriesStmt = sqlsrv_query($conn, $categoriesSql);
$categories = [];
while ($cat = sqlsrv_fetch_array($categoriesStmt, SQLSRV_FETCH_ASSOC)) {
    $categories[] = $cat;
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Book - BookShop Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .edit-container {
            max-width: 800px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .edit-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
        }
        
        .form-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 2px solid #ecf0f1;
        }
        
        .form-header h2 {
            color: #2c3e50;
            margin: 0;
        }
        
        .back-btn {
            background: #95a5a6;
            color: white;
            padding: 8px 16px;
            border: none;
            border-radius: 4px;
            text-decoration: none;
            display: inline-block;
        }
        
        .back-btn:hover {
            background: #7f8c8d;
        }
        
        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 20px;
        }
        
        .form-group {
            margin-bottom: 20px;
        }
        
        .form-group.full-width {
            grid-column: span 2;
        }
        
        .form-group label {
            display: block;
            margin-bottom: 5px;
            font-weight: bold;
            color: #555;
        }
        
        .form-group input,
        .form-group select,
        .form-group textarea {
            width: 100%;
            padding: 10px;
            border: 1px solid #ddd;
            border-radius: 4px;
            font-size: 16px;
        }
        
        .form-group textarea {
            resize: vertical;
            min-height: 120px;
        }
        
        .button-group {
            display: flex;
            gap: 10px;
            margin-top: 30px;
        }
        
        .update-btn {
            background: #3498db;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16;
        }
        
        .update-btn:hover {
            background: #2980b9;
        }
        
        .cancel-btn {
            background: #e74c3c;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
            text-decoration: none;
            display: inline-block;
        }
        
        .cancel-btn:hover {
            background: #c0392b;
        }
        
        .alert {
            padding: 12px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .alert-success {
            background: #d4edda;
            color: #155724;
            border: 1px solid #c3e6cb;
        }
        
        .alert-error {
            background: #f8d7da;
            color: #721c24;
            border: 1px solid #f5c6cb;
        }
        
        .book-info {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 4px;
            margin-bottom: 20px;
        }
        
        .book-info p {
            margin: 5px 0;
            color: #6c757d;
        }
    </style>
</head>
<body>
    <header>
        <h1>BookShop Admin</h1>
        <nav>
            <ul class="nav-list">
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage-books.php" class="active">Manage Books</a></li>
                <li><a href="view-orders.php">Orders</a></li>
                <li><a href="users.php">Users</a></li>
                <li><a href="audit-logs.php">Audit Logs</a></li>
                <li><a href="../index.html">View Store</a></li>
            </ul>
        </nav>
    </header>
    
    <main class="edit-container">
        <div class="edit-form">
            <div class="form-header">
                <h2>Edit Book</h2>
                <a href="manage-books.php" class="back-btn">← Back to Books</a>
            </div>
            
            <?php if ($message): ?>
                <div class="alert alert-success"><?php echo $message; ?></div>
            <?php endif; ?>
            
            <?php if ($error): ?>
                <div class="alert alert-error"><?php echo $error; ?></div>
            <?php endif; ?>
            
            <div class="book-info">
                <p><strong>Book ID:</strong> #<?php echo $book['ProductID']; ?></p>
                <p><strong>Added:</strong> <?php echo $book['CreatedDate']->format('M d, Y'); ?></p>
            </div>
            
            <form method="POST" action="edit-book.php?id=<?php echo $bookId; ?>">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Book Title</label>
                        <input type="text" name="title" value="<?php echo htmlspecialchars($book['Title']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" value="<?php echo htmlspecialchars($book['Author']); ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" step="0.01" min="0" 
                               value="<?php echo $book['Price']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" min="0" 
                               value="<?php echo $book['StockQuantity']; ?>" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['CategoryID']; ?>"
                                        <?php echo $book['CategoryID'] == $cat['CategoryID'] ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label>Image URL</label>
                        <input type="text" value="<?php echo htmlspecialchars($book['ImageURL']); ?>" disabled>
                        <small style="color: #7f8c8d;">Image URL cannot be changed in this version</small>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" required><?php echo htmlspecialchars($book['Description']); ?></textarea>
                    </div>
                </div>
                
                <div class="button-group">
                    <button type="submit" name="update_book" class="update-btn">Update Book</button>
                    <a href="manage-books.php" class="cancel-btn">Cancel</a>
                </div>
            </form>
        </div>
    </main>
</body>
</html>
