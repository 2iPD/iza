# Instalar este quiz em outra hospedagem

Este ZIP contém somente o quiz selecionado (versão publicada), com a aplicação PHP, editor, estilos, imagens embutidas, perguntas, pontos, faixas e fluxos. Não contém contatos, senhas, tokens nem outros quizzes. O webhook foi deixado vazio; configure uma integração nova no painel, se desejar.

Requisitos: domínio HTTPS, PHP 8.2 ou superior, MySQL, PDO MySQL, cURL, acesso a terminal/SSH e servidor Apache compatível com .htaccess. Não funciona em hospedagem apenas estática nem offline. Não depende da hospedagem original ou do GitHub.

1. Extraia `quiz-adv/` e `quiz-1/` na raiz pública da nova hospedagem. Use um banco novo, dedicado a este pacote. Não sobrescreva uma instalação existente ou uma pasta quiz-1 já utilizada.
2. Copie `quiz-adv/private/config.example.php` como `quiz-adv-config.php` no diretório acima da raiz pública. Preencha APP_ORIGIN com o novo domínio (sem caminho), dados do novo banco, ADMIN_EMAIL e APP_SECRET (64 caracteres aleatórios). Mantenha LOCAL_PUBLICATION true; não precisa configurar GitHub.
3. Gere o hash da nova senha com `php quiz-adv/private/password-hash.php` e preencha ADMIN_PASSWORD_HASH na configuração. Não coloque senhas ou configurações privadas em repositórios públicos.
4. Execute `php quiz-adv/private/install.php` e depois `php quiz-adv/private/restore-export.php` no terminal. A importação só aceita uma instalação sem quizzes, cria UUIDs de quiz e proprietário próprios e não altera instalações já preenchidas.
5. Abra `https://NOVO-DOMINIO/quiz-1/` para responder e `https://NOVO-DOMINIO/quiz-adv/` para editar. Novos contatos ficam no banco da nova hospedagem. O botão Atualizar página publica localmente, sem GitHub.

Após conferir a importação, você pode apagar `quiz-adv/private/quiz-export.json` e `restore-export.php` da nova hospedagem. Mantenha os demais arquivos da aplicação e a configuração privada para o quiz continuar funcionando.
