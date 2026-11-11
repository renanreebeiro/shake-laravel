# Central de Documentos Shake Burger

Versão Laravel 13, PHP 8.3 ou superior e MySQL. O banco guarda contas
e modelos. O PDF é montado no navegador com o timbrado extraído da
referência. Os dados de clientes não são enviados ao servidor.

## Testar em um computador com PHP, Composer e MySQL

1. Crie o banco shake_documentos e um usuário MySQL com acesso a ele.
2. Dentro desta pasta, execute:

   ```sh
   composer install
   cp .env.example .env
   php artisan key:generate
   ```

3. Abra o arquivo .env e preencha DB_USERNAME e DB_PASSWORD com os dados
   do MySQL. Se necessário, ajuste DB_HOST e DB_PORT.
4. Rode os comandos a seguir. A senha da conta será pedida na tela.

   ```sh
   php artisan migrate --seed
   php artisan shake:usuario seu-email@exemplo.com --admin
   php artisan serve
   ```

5. Abra http://localhost:8000 e entre com a conta criada. Para criar
   contas de emissão, use o mesmo comando sem --admin.

## O que revisar

Emita o contrato com dados fictícios. O modelo inicial reproduz a
variante com personagem. Digite os valores em formato brasileiro e
preencha os valores por extenso. Em Modelos, administradores podem
cadastrar texto com marcadores, editar e desativar. As variantes de
entrega, os recibos e os campos mais avançados do editor ainda precisam
ser migrados.

Para publicar, use um servidor PHP com HTTPS e MySQL e configure a
raiz do site para a pasta public/. Use APP_ENV=production,
APP_DEBUG=false e SESSION_SECURE_COOKIE=true. O endereço localhost é
apenas para teste no computador que executa a aplicação.
