<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login</title>
  <link href="https://stackpath.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
  <link href="public/css/app.css" rel="stylesheet">
</head>

<body>
  <div class="container d-flex justify-content-center align-items-center vh-100">
    <div class="col-md-4">
      <h2 class="text-center">Login</h2>
      <?php if (isset($_GET['error']) && $_GET['error'] === 'invalid_credentials') : ?>
        <div class="alert alert-danger">Credenciais inválidas. Tente novamente.</div>
      <?php endif; ?>
      <form action="src/controllers/LoginController.php" method="post">
        <div class="form-group">
          <label for="email">E-mail</label>
          <input type="email" name="email" id="email" class="form-control" required>
        </div>
        <div class="form-group">
          <label for="password">Senha</label>
          <input type="password" name="password" id="password" class="form-control" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Login</button>
      </form>
    </div>
  </div>
</body>

</html>