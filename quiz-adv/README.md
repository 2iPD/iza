# QUIZ ADV na Hostinger

Criador de quizzes editáveis com etapas de pergunta, carregamento simulado e resultados diferentes por resposta. O painel fica em **/quiz-adv/**. Os visitantes usam **/quiz-1**, **/quiz-2** etc., sem login. A página inicial e o treinamento existentes neste repositório foram preservados.

## Ativar uma vez na Hostinger

1. Use uma hospedagem com **PHP 8.2 ou superior, MySQL/MariaDB, HTTPS e reescrita Apache/LiteSpeed**. Ative as extensões `pdo_mysql`, `curl`, `mbstring` e `intl`. O modo de instalação precisa de acesso SSH/terminal para executar PHP; o plano precisa oferecer isso. GitHub Pages e hospedagem somente estática não executam este backend.
2. No hPanel, conecte o repositório **2iPD/iza**, branch **main**, à raiz do site (`public_html`). Se a pasta já contiver o site, faça backup e use o fluxo de atualização Git existente. Não apague o site para configurar a ferramenta. Ative a implantação automática de commits seguindo a documentação da Hostinger: https://www.hostinger.com/support/1583302-how-to-deploy-a-git-repository-in-hostinger/ . Não configure Vercel para este aplicativo PHP.
3. Crie o banco MySQL e um usuário exclusivo com acesso somente a esse banco.
4. Copie `quiz-adv/private/config.example.php` como **quiz-adv-config.php no diretório acima de public_html**. Preencha o domínio HTTPS, banco, e-mail do administrador e as credenciais abaixo. Alternativamente use `quiz-adv/private/config.local.php`, que está bloqueado pelo `.htaccess` e ignorado pelo Git. Prefira o arquivo fora da pasta pública. Nunca adicione senhas, tokens ou contatos ao repositório.
5. Gere `APP_SECRET` com `php -r "echo bin2hex(random_bytes(32)), PHP_EOL;"`. Para `ADMIN_PASSWORD_HASH`, execute `php public_html/quiz-adv/private/password-hash.php`, digite uma senha exclusiva de pelo menos 12 caracteres e copie somente o hash para a configuração. A senha é digitada no terminal, nunca no código ou chat.
6. Para `GITHUB_TOKEN`, crie um **fine-grained personal access token** limitado ao repositório **2iPD/iza**, com **Contents: Read and write**. Guarde somente na configuração privada da Hostinger. A conexão do GitHub usada nesta conversa não substitui a credencial do aplicativo hospedado. O token precisa poder escrever na branch configurada; proteções que proíbem commits diretos precisam de um fluxo autorizado compatível. Documentação: https://docs.github.com/en/authentication/keeping-your-account-and-data-secure/managing-your-personal-access-tokens .
7. No terminal da hospedagem execute `php public_html/quiz-adv/private/install.php` (ajuste o caminho ao seu diretório atual). O script cria as tabelas e o administrador. Pode ser executado novamente sem apagar dados nem trocar senhas existentes.
8. Abra **https://SEU-DOMINIO/quiz-adv/**, entre no editor, monte o quiz e clique em **Publicar quiz**. O painel mostra o link com os botões **Copiar link** e **Abrir quiz**.

## Como a publicação funciona

- A primeira publicação reserva um endereço como `/quiz-1`, usando uma transação no banco. O número é apenas um apelido público; todos os IDs de usuários, quizzes, sessões, etapas, opções e contatos são UUIDs.
- O servidor cria `quiz-1/index.html` no GitHub e só ativa a versão pública depois da confirmação do GitHub. Essa página carrega o quiz do backend da Hostinger. Ela não contém a pontuação, a lógica completa, webhooks, senhas nem contatos.
- O `.htaccess` encaminha `/quiz-1` ao backend. Depois de instalar a ferramenta na Hostinger, os novos links funcionam pelo servidor assim que a publicação é confirmada, mesmo enquanto o Git automático atualiza os arquivos HTML. O backend e seu banco são indispensáveis: copiar apenas as páginas HTML para uma hospedagem estática não basta.
- **Salvar** altera o rascunho. **Atualizar página** publica a nova versão no mesmo endereço. Uma falha do GitHub mantém a versão pública anterior. Sessões já iniciadas mantêm a versão com que começaram.
- Os contatos reais ficam no MySQL e aparecem em **Contatos** (até os mil mais recentes por quiz). O modo **Testar quiz** não salva contatos e não envia webhooks. Exportação e sincronização de contatos com CRM não estão implementadas além do webhook opcional.
- Webhooks são enviados uma vez, após salvar o contato. Não há fila de reenvio; falhas não apagam o contato. Apenas os hosts listados em `WEBHOOK_HOSTS` são aceitos. Não autorize hosts internos nem servidores sem controle de segurança.

## Proteções aplicadas

Autenticação somente no editor; cookies de administrador e de visitante independentes, HttpOnly, Secure e SameSite; sessões aleatórias de visitante; autorização por proprietário no servidor; consultas PDO preparadas; validação estrita de formatos, limites e campos permitidos; escape dos textos exibidos; origem e cabeçalho obrigatórios nas gravações; limite persistente de tentativas; pontuação, destinos, tempo de carregamento e consentimento validados pelo servidor; controle de versão para impedir respostas duplicadas e alterações concorrentes. Os diretórios `private` e `tests` são bloqueados no servidor web. Não há senha padrão nem cadastro público de administradores.

Mantenha PHP e a hospedagem atualizados, faça backup do banco e do arquivo privado e defina um prazo de retenção dos contatos adequado ao negócio. Sessões expiram após 24 horas. Periodicamente exclua `qa_rate_limits` com `expires_at < UNIX_TIMESTAMP()`; registros de sessões referenciados por contatos precisam ser preservados ou tratados com uma política de retenção explícita.

## Verificar a instalação

1. Sem login: `/quiz-adv/` redireciona para entrar; `/quiz-adv/private/config.example.php` e `/quiz-adv/tests/run.php` retornam acesso negado.
2. Depois de publicar: em uma janela anônima, o link `/quiz-1` abre sem login e permite chegar ao resultado após o contato.
3. O contato aparece apenas no painel do proprietário. A etapa de carregamento respeita o tempo definido e as respostas seguem os destinos configurados.
4. A página inicial e `/treinamento-maria-da-penha` continuam funcionando.

## Testes locais

`php -d extension=pdo_sqlite quiz-adv/tests/run.php`

Os testes usam SQLite em memória e um transporte GitHub simulado. Verificam propriedade, sessões, pontuação, ramificações, tempo de carregamento, publicação, falhas e preservação de arquivos. Não conectam à Hostinger nem substituem a validação final com seu MySQL e domínio. Também foram verificados a sintaxe PHP e os arquivos JavaScript. Nenhuma credencial real foi incluída.
