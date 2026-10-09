# Escala Pay — rodar a demo local igual à original

Painel em http://127.0.0.1:8090 com o redesign Escala Pay e os mesmos dados de demonstração
(clientes, pedidos e produtos fictícios). Pré-requisito: Docker Desktop ligado.

Rode tudo a partir da raiz do repositório.

## 1. Configuração

```bash
cp demo-local/demo.env.example demo-local/demo.env
chmod +x demo-local/demo.sh
```

## 2. Subir (a primeira vez demora: compila a imagem e instala as dependências)

```bash
./demo-local/demo.sh up -d --build
```

Espere `./demo-local/demo.sh ps` mostrar o `app` como `healthy` e http://127.0.0.1:8090/login responder.

## 3. Importar os dados da demo

```bash
gunzip -c demo-local/getfy_demo.sql.gz | ./demo-local/demo.sh exec -T mysql sh -c 'mysql -uroot -p"$MYSQL_ROOT_PASSWORD" "$MYSQL_DATABASE"'
./demo-local/demo.sh exec app php artisan cache:clear
```

## 4. Definir a senha do administrador (usuário id 1)

```bash
./demo-local/demo.sh exec app php artisan tinker --execute="App\Models\User::find(1)->forceFill(['password' => bcrypt('SUA_SENHA')])->save(); echo App\Models\User::find(1)->email;"
```

O comando imprime o e-mail do administrador. Entre em http://127.0.0.1:8090/login com esse e-mail e a senha escolhida.

## Modo edição ao vivo (opcional)

Para editar o front e ver na hora, monte o código no container e rode o Vite no host:

```bash
npm install
DEV=1 ./demo-local/demo.sh up -d app
./demo-local/demo.sh exec app ln -sf /var/www/html/public/hot storage/framework/vite.hot
npm run dev
```

Sem o Vite, o painel usa o build já compilado em `public/build`. Depois de mudar o front, rode `npm run build`.

## Cuidados

- **Nunca rode `php artisan test` dentro do container:** as variáveis de banco do container
  sobrescrevem o sqlite do `phpunit.xml` e o teste apaga o MySQL da demo.
- `demo-local/demo.env` fica fora do Git (tem as senhas locais).
- Comandos úteis: `./demo-local/demo.sh ps`, `./demo-local/demo.sh logs -f app`, `./demo-local/demo.sh down`.
