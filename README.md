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

### Google Agenda da Shake Burger

1. No Google Cloud, habilite a Google Calendar API e crie um cliente OAuth
   do tipo **Aplicativo da Web**. Cadastre o retorno
   `https://documentos.shakeburger.com.br/integracoes/google-agenda/retorno`.
2. Compartilhe somente a agenda EVENTOS SHAKE com uma conta Google dedicada
   à integração, com permissão para editar eventos. Configure
   `SHAKE_GOOGLE_ACCOUNT_EMAIL`, `SHAKE_GOOGLE_CALENDAR_ID`,
   `SHAKE_GOOGLE_CLIENT_ID` e `SHAKE_GOOGLE_CLIENT_SECRET` no `.env` privado.
3. Rode `php artisan migrate --force` e `php artisan config:clear`. Entre como
   administrador em Eventos, clique em **Conectar Google Agenda** e autorize
   usando a conta dedicada. O token é criptografado no armazenamento privado.
4. Execute `php artisan shake:calendar-sync` para validar a conexão. Depois,
   configure a hospedagem para executar `php artisan schedule:run` a cada minuto
   na pasta do projeto. A sincronização roda a cada cinco minutos.
   Com público externo em modo Teste, a autorização do Google Agenda expira
   após sete dias. Para uso contínuo, configure a publicação no Google Cloud.

A primeira execução traz eventos avulsos existentes no Google e envia os eventos
ativos já cadastrados na Central. Depois disso, o vínculo é mantido pelo ID do
evento. Se o mesmo compromisso já foi cadastrado separadamente nos dois lados,
revise esses registros antes da primeira execução para evitar cópias.

A integração espelha título, endereço, datas e horários, inclusive eventos de
dia inteiro. Um evento antigo da Central sem horário de término recebe uma hora
de duração no Google. Eventos recorrentes e tipos especiais do Google são
ignorados. Campos internos, como observações, responsável, quantidade e status
do Kanban, ficam na Central. Uma exclusão no Google marca o evento vinculado como
cancelado na Central. Cancelar pelo Kanban não exclui um evento do Google.

Se os mesmos dados forem alterados dos dois lados entre sincronizações, a rotina
preserva as duas versões e conta um conflito no resultado. Para resolver, deixe
título, local e horários iguais nos dois lados e rode a sincronização novamente.

### Cobertura audiovisual

- Aplique a migração com `php artisan migrate --force` e publique
  `public/assets/events.css` na pasta de arquivos públicos da hospedagem.
- Habilite a Google Drive API e cadastre o retorno OAuth
  `https://documentos.shakeburger.com.br/eventos/cobertura/drive/retorno`.
  Configure `SHAKE_DRIVE_CLIENT_ID` e `SHAKE_DRIVE_CLIENT_SECRET` no `.env` privado.
  A autorização do Drive é separada da Agenda. O Google solicita acesso amplo
  ao Drive para que a Central possa validar uma pasta existente informada por
  link e administrar suas subpastas. Use uma conta empresarial com arquivos
  destinados à operação. O administrador conecta em **Eventos → Configurar
  Google Drive da cobertura** e informa uma pasta própria e privada no Meu Drive.
- O e-mail usa o SMTP da Central. Configure `MAIL_MAILER=smtp`, o servidor,
  as credenciais e o remetente, e habilite `SHAKE_COVERAGE_EMAIL_ENABLED=true`.
- Para WhatsApp, use um número da Cloud API e um modelo aprovado em português
  do Brasil com sete parâmetros no corpo, nesta ordem: nome, evento, data,
  horário, endereço, prazo e links. Use o canal para profissionais que aceitaram
  receber mensagens. Configure as variáveis `SHAKE_WHATSAPP_*` do `.env.example`.
- Rode `php artisan config:clear` depois de configurar. O salvamento solicita
  processamento imediato. Na Hostinger, confirme que o cron chama
  `php artisan schedule:run` a cada minuto ou configure uma tarefa específica
  com `php artisan shake:coverage-sync` para processar coberturas pendentes.
- A pasta principal de um evento permanece vinculada mesmo se a configuração
  global mudar. As pastas por função isolam o acesso inclusive em coberturas
  de um único tipo. Mudanças de contato removem as permissões atribuídas pela
  integração na função. O status Enviado significa aceito pelo serviço de envio.
  Se o serviço não confirmar o envio, confira se houve entrega antes de autorizar
  a repetição na ficha do evento.

### Sugestões de endereço dos eventos

Habilite Maps JavaScript API e Places API (New) em um projeto
Google Cloud com faturamento configurado. Crie uma chave restrita a essas APIs
e ao site `https://documentos.shakeburger.com.br/*`. Defina a chave em
`SHAKE_MAPS_BROWSER_KEY` no `.env` da Hostinger, rode `php artisan config:clear`
e publique `public/assets/events.css`. A chave é usada pelo navegador.

Ao selecionar uma sugestão, a Central salva o endereço e o ID do local para
abrir o ponto no Google Maps. A ficha continua aceitando o preenchimento manual.

Excluir um evento o retira da Central. A agenda Google e os arquivos no Drive
são preservados, e a sincronização ignora o evento excluído.
