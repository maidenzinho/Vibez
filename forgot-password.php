<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Senha</title>
    <style>
        body {
            background-color: #121d2f;
            color: white;
            font-family: 'Segoe UI', sans-serif;
            display: flex;
            justify-content: center;
            align-items: center;
            height: 100vh;
        }
        form {
            background-color: #1c2833;
            padding: 30px;
            border-radius: 10px;
            width: 90%;
            max-width: 400px;
            box-shadow: 0 0 10px rgba(0,0,0,0.5);
        }
        input, button {
            width: 90%;
            padding: 12px;
            margin: 10px 0;
            border-radius: 8px;
            border: none;
            outline: none;
            display: flex;
            margin-left: auto;
            margin-right: auto;
        }
        input {
            background-color: #2c3e50;
            color: white;
        }
        button {
            background-color: #00bfff;
            color: white;
            font-weight: bold;
            cursor: pointer;
        }
        button:hover {
            background-color: #009acd;
        }
    </style>
</head>
<body>
    <form class="form-group" method="post" action="email.php">
        <h2>Esqueceu sua senha?</h2>
        <input type="email" name="email" placeholder="Digite seu e-mail" required>
        <button type="submit">Enviar</button>
    </form>
</body>
</html>