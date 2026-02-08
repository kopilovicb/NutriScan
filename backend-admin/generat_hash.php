<?php
/**
 * GENERATOR HASH-A ZA LOZINKE
 *
 * Koristi ovaj fajl da generišeš hash za novu lozinku
 * Otvori ga u browseru i unesi željenu lozinku
 */

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
    $password = $_POST['password'];
    $hash = password_hash($password, PASSWORD_DEFAULT);

    $generatedHash = htmlspecialchars($hash);
    $message = "<div style='background: #d4edda; padding: 15px; border-radius: 5px; margin: 20px 0;'>
                    <strong>Generisan hash:</strong><br>
                    <code style='background: white; padding: 10px; display: block; margin-top: 10px; word-break: break-all;'>$generatedHash</code>
                    <p style='margin-top: 10px; color: #155724;'>Kopiraj ovaj hash i stavi ga u SQL upit za lozinka_hash kolonu.</p>
                </div>";
}
?>
<!DOCTYPE html>
<html lang="sr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Generator Hash-a za Lozinke</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            min-height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            padding: 20px;
        }

        .container {
            background: white;
            border-radius: 20px;
            padding: 40px;
            max-width: 600px;
            width: 100%;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.3);
        }

        h1 {
            color: #333;
            margin-bottom: 10px;
        }

        p {
            color: #666;
            margin-bottom: 20px;
        }

        .input-group {
            margin-bottom: 20px;
        }

        label {
            display: block;
            margin-bottom: 8px;
            color: #555;
            font-weight: 500;
        }

        input {
            width: 100%;
            padding: 12px 15px;
            border: 2px solid #e0e0e0;
            border-radius: 8px;
            font-size: 14px;
            transition: all 0.3s;
            outline: none;
        }

        input:focus {
            border-color: #06b6d4;
            box-shadow: 0 0 0 3px rgba(6, 182, 212, 0.1);
        }

        button {
            width: 100%;
            padding: 14px;
            background: linear-gradient(135deg, #06b6d4 0%, #10b981 100%);
            color: white;
            border: none;
            border-radius: 8px;
            font-size: 16px;
            font-weight: 600;
            cursor: pointer;
            transition: transform 0.2s;
        }

        button:hover {
            transform: translateY(-2px);
        }

        code {
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
    </style>
</head>
<body>
<div class="container">
    <h1>🔐 Generator Hash-a</h1>
    <p>Unesi lozinku da generišeš hash za bazu podataka</p>

    <form method="POST">
        <div class="input-group">
            <label for="password">Lozinka:</label>
            <input type="text" id="password" name="password" placeholder="npr. admin123" required>
        </div>

        <button type="submit">Generiši Hash</button>
    </form>

    <?php if (isset($message)) echo $message; ?>

    <div style="margin-top: 30px; padding-top: 20px; border-top: 2px solid #f0f0f0;">
        <h3 style="color: #333; margin-bottom: 10px;">Kako koristiti:</h3>
        <ol style="color: #666; line-height: 1.8; padding-left: 20px;">
            <li>Unesi željenu lozinku u polje iznad</li>
            <li>Klikni "Generiši Hash"</li>
            <li>Kopiraj generisan hash</li>
            <li>Stavi ga u SQL upit za <code>lozinka_hash</code> kolonu</li>
        </ol>
    </div>
</div>
</body>
</html>