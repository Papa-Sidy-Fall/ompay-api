<!DOCTYPE html>
<html lang="fr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Code de confirmation OmPay</title>
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .header { background-color: #007bff; color: white; padding: 20px; text-align: center; }
        .content { padding: 20px; background-color: #f8f9fa; }
        .code { font-size: 24px; font-weight: bold; color: #007bff; text-align: center; padding: 20px; background-color: white; border: 2px solid #007bff; margin: 20px 0; }
        .footer { text-align: center; padding: 20px; color: #666; }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>OmPay - Code de confirmation</h1>
        </div>

        <div class="content">
            <p>Bonjour,</p>

            <p>Votre code de confirmation de transaction OmPay est :</p>

            <div class="code">{{ $code }}</div>

            <p>Ce code expire le {{ $expires_at }}</p>

            <p>Si vous n'avez pas demandé ce code, ignorez cet email.</p>

            <p>Cordialement,<br>
            L'équipe OmPay</p>
        </div>

        <div class="footer">
            <p>Cet email a été envoyé automatiquement, merci de ne pas y répondre.</p>
        </div>
    </div>
</body>
</html>