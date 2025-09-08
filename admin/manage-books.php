<?php
session_start();

// Check admin access
if (!isset($_SESSION['is_admin'])) {
    header('Location: login.php');
    exit;
}

require_once '../db_config.php';

// Handle book operations
$message = '';
$error = '';

// Add new book
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['add_book'])) {
    $title = $_POST['title'];
    $author = $_POST['author'];
    $description = $_POST['description'];
    $price = $_POST['price'];
    $stock = $_POST['stock'];
    $category = $_POST['category'];
    
    // VULNERABILITY: SQL Injection
    $insertSql = "INSERT INTO Products (Title, Author, Description, Price, StockQuantity, CategoryID, ImageURL) 
                  VALUES ('$title', '$author', '$description', $price, $stock, $category, '/images/default-book.jpg')";
    
    if (sqlsrv_query($conn, $insertSql)) {
        $message = "Book added successfully!";
        
        // Log action
        $logSql = "INSERT INTO AuditLogs (UserID, Action, TableAffected, UserInput) 
                  VALUES (" . $_SESSION['admin_id'] . ", 'Book Added', 'Products', 'Added book: $title')";
        sqlsrv_query($conn, $logSql);
    } else {
        $error = "Failed to add book.";
    }
}

// Delete book
if (isset($_GET['delete'])) {
    $bookId = $_GET['delete'];
    // VULNERABILITY: No CSRF protection
    $deleteSql = "DELETE FROM Products WHERE ProductID = $bookId";
    
    if (sqlsrv_query($conn, $deleteSql)) {
        $message = "Book deleted successfully!";
    } else {
        $error = "Failed to delete book.";
    }
}

// Get all books
$booksSql = "SELECT p.*, c.CategoryName FROM Products p 
             LEFT JOIN Categories c ON p.CategoryID = c.CategoryID 
             ORDER BY p.ProductID DESC";
$booksStmt = sqlsrv_query($conn, $booksSql);

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
    <title>Manage Books - BookShop Admin</title>
    <link rel="stylesheet" href="../style.css">
    <style>
        .manage-container {
            max-width: 1200px;
            margin: 20px auto;
            padding: 20px;
        }
        
        .add-book-form {
            background: white;
            padding: 30px;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            margin-bottom: 30px;
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
            min-height: 100px;
        }
        
        .add-btn {
            background: #27ae60;
            color: white;
            padding: 12px 30px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 16px;
        }
        
        .add-btn:hover {
            background: #229954;
        }
        
        .books-table {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 8px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        
        .books-table table {
            width: 100%;
            border-collapse: collapse;
        }
        
        .books-table th {
            background: #2c3e50;
            color: white;
            padding: 15px;
            text-align: left;
        }
        
        .books-table td {
            padding: 12px 15px;
            border-bottom: 1px solid #ecf0f1;
        }
        
        .books-table tr:hover {
            background: #f8f9fa;
        }
        
        .action-buttons {
            display: flex;
            gap: 10px;
        }
        
        .edit-btn {
            background: #3498db;
            color: white;
            padding: 5px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
        }
        
        .delete-btn {
            background: #e74c3c;
            color: white;
            padding: 5px 15px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            text-decoration: none;
            font-size: 14px;
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
        
        .stock-low {
            color: #e74c3c;
            font-weight: bold;
        }
        
        .stock-good {
            color: #27ae60;
        }
    </style>
</head>
<body>
<div class="security-warning-banner">
    <span class="warning-icon">⚠️</span>
    <span class="warning-text">
        <strong>SECURITY DEMONSTRATION SITE</strong> - This website is intentionally vulnerable and contains fictional data. 
        DO NOT enter real personal or financial information. This is an educational security testing environment.
    </span>
</div>
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
    
    <main class="manage-container">
        <?php if ($message): ?>
            <div class="alert alert-success"><?php echo $message; ?></div>
        <?php endif; ?>
        
        <?php if ($error): ?>
            <div class="alert alert-error"><?php echo $error; ?></div>
        <?php endif; ?>
        
        <div class="add-book-form">
            <h2>Add New Book</h2>
            <form method="POST" action="manage-books.php">
                <div class="form-grid">
                    <div class="form-group">
                        <label>Book Title</label>
                        <input type="text" name="title" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Author</label>
                        <input type="text" name="author" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Price ($)</label>
                        <input type="number" name="price" step="0.01" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Stock Quantity</label>
                        <input type="number" name="stock" min="0" required>
                    </div>
                    
                    <div class="form-group">
                        <label>Category</label>
                        <select name="category" required>
                            <option value="">Select Category</option>
                            <?php foreach ($categories as $cat): ?>
                                <option value="<?php echo $cat['CategoryID']; ?>">
                                    <?php echo htmlspecialchars($cat['CategoryName']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group full-width">
                        <label>Description</label>
                        <textarea name="description" required></textarea>
                    </div>
                </div>
                
                <button type="submit" name="add_book" class="add-btn">Add Book</button>
            </form>
        </div>
        
        <div class="books-table">
            <h2 style="padding: 20px;">Current Inventory</h2>
            <table>
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Title</th>
                        <th>Author</th>
                        <th>Category</th>
                        <th>Price</th>
                        <th>Stock</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    if ($booksStmt) {
                        while ($book = sqlsrv_fetch_array($booksStmt, SQLSRV_FETCH_ASSOC)) {
                            $stockClass = $book['StockQuantity'] < 10 ? 'stock-low' : 'stock-good';
                            
                            echo '<tr>';
                            echo '<td>' . $book['ProductID'] . '</td>';
                            echo '<td>' . htmlspecialchars($book['Title']) . '</td>';
                            echo '<td>' . htmlspecialchars($book['Author']) . '</td>';
                            echo '<td>' . htmlspecialchars($book['CategoryName']) . '</td>';
                            echo '<td>$' . number_format($book['Price'], 2) . '</td>';
                            echo '<td class="' . $stockClass . '">' . $book['StockQuantity'] . '</td>';
                            echo '<td class="action-buttons">';
                            echo '<a href="edit-book.php?id=' . $book['ProductID'] . '" class="edit-btn">Edit</a>';
                            // VULNERABILITY: No confirmation for delete
                            echo '<a href="manage-books.php?delete=' . $book['ProductID'] . '" class="delete-btn">Delete</a>';
                            echo '</td>';
                            echo '</tr>';
                        }
                    }
                    ?>
                </tbody>
            </table>
        </div>
    </main>
</body>
</html>
