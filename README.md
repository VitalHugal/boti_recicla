<p align="center"><a href="https://github.com/Vgress/Botirecicla" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Botirecicla Logo"></a></p>

<h1 align="center">Botirecicla</h1>

<p align="center">
  Plataforma de incentivo à reciclagem.
</p>

## Sobre o Projeto

O Botirecicla é uma aplicação web desenvolvida em Laravel que serve como backend para uma plataforma de incentivo à reciclagem. A ideia central é permitir que usuários se cadastrem, participem de pesagens de materiais recicláveis e acumulem créditos que podem ser trocados por produtos.

A aplicação foi estruturada para ser consumida por diferentes clientes, como totens de pesagem e aplicativos móveis, através de uma API RESTful.

## Funcionalidades

- **Cadastro e Autenticação de Usuários**: Sistema de registro para novos usuários.
- **Gerenciamento de Participações**:
    - Início e fim de pesagens de recicláveis.
    - Confirmação e validação do peso.
    - Cálculo de resultados e créditos.
- **Catálogo e Resgate de Produtos**:
    - Listagem de produtos disponíveis para troca.
    - API para resgate de produtos utilizando os créditos acumulados.
- **APIs Seguras**:
    - Rotas protegidas para garantir que apenas aplicações autorizadas (totem, mobile) consumam os dados.
    - API externa com autenticação baseada em token para parceiros.
- **Monitoramento**: Integração com Zabbix para monitoramento de jobs em background.

## Tecnologias Utilizadas

- **Backend**: Laravel
- **Banco de Dados**: MySQL (configurado via `.env`, mas flexível pelo Laravel)
- **Servidor**: Apache/Nginx (via XAMPP, WAMP, etc.)
- **Gerenciador de Dependências**: Composer

## ORM Eloquent

O projeto faz uso extensivo do Eloquent, o ORM (Object-Relational Mapper) integrado do Laravel, para interagir com o banco de dados de forma elegante e intuitiva. Cada tabela do banco de dados possui um "Model" correspondente que é usado para interagir com essa tabela.

### Models Principais

-   `User`: Representa os usuários da aplicação.
-   `Participation`: Armazena os registros de participação dos usuários nas pesagens.
-   `Product`: Modela os produtos disponíveis para resgate.
-   `Exits`: Registra as saídas de produtos resgatados pelos usuários.

### Características Utilizadas

-   **Mapeamento de Atributos**: A propriedade `$fillable` nos models é usada para definir quais atributos podem ser atribuídos em massa, protegendo contra vulnerabilidades de atribuição em massa.
-   **Soft Deletes**: Models como `User` e `Participation` utilizam o trait `SoftDeletes`. Isso significa que, quando um registro é "excluído", ele não é removido permanentemente do banco de dados. Em vez disso, o atributo `deleted_at` é preenchido, permitindo que os dados sejam recuperados posteriormente, se necessário.
-   **Relacionamentos**: A lógica nos controladores e comandos (por exemplo, `Participation::with('user')`) demonstra o uso de relacionamentos do Eloquent para carregar dados relacionados de forma eficiente (Eager Loading), evitando o problema de N+1 queries.
-   **Convenções**: O Eloquent segue convenções de nomenclatura que permitem ao Laravel assumir os nomes das tabelas e chaves estrangeiras, simplificando o desenvolvimento.

Essa abordagem permite que o código seja mais legível, manutenível e seguro, abstraindo as consultas SQL complexas.

## Primeiros Passos

Siga os passos abaixo para configurar e executar o projeto em seu ambiente de desenvolvimento local.

### Pré-requisitos

- PHP >= 8.2
- Composer
- Servidor Web (XAMPP, WAMP, Laragon, etc.)
- Node.js e NPM (para o frontend, se aplicável)

### Instalação

1. **Clone o repositório:**
   ```bash
   git clone https://github.com/Vgress/Botirecicla.git
   cd Botirecicla/back
   ```

2. **Instale as dependências do PHP:**
   ```bash
   composer install
   ```

3. **Configure o ambiente:**
   - Copie o arquivo de exemplo `.env.example` para `.env`.
   - Gere uma chave para a aplicação:
     ```bash
     php artisan key:generate
     ```
   - Configure as variáveis de ambiente no arquivo `.env`, principalmente as de conexão com o banco de dados (`DB_*`).

4. **Execute as Migrations:**
   Crie o banco de dados com o nome definido em `DB_DATABASE` e execute as migrations para criar as tabelas.
   ```bash
   php artisan migrate
   ```

5. **Inicie o servidor de desenvolvimento:**
   ```bash
   php artisan serve
   ```
   A aplicação estará disponível em `http://localhost:8000`.

## Comandos Artisan

O projeto inclui comandos personalizados para gerar relatórios e processar dados.

- **`php artisan app:all_exists`**: Gera uma tabela `all_exits` com um relatório de todos os usuários e os produtos que eles resgataram.
- **`php artisan app:all_participations`**: Cria a tabela `all_participations` com um compilado de todas as participações dos usuários, incluindo detalhes de pesagem e resgates.
- **`php artisan app:decrypt-all-users`**: Descriptografa e armazena as informações de todos os usuários na tabela `info_ger` para facilitar a consulta.
- **`php artisan app:participations_pending`**: Gera a tabela `participation_pendings` listando todas as participações com créditos pendentes de resgate.
- **`php artisan app:report_info`**: Cria a tabela `report_info` com um relatório geral das participações, unindo dados de usuários, produtos e pesagens.

## Endpoints da API

Abaixo estão alguns dos principais endpoints disponíveis na API.

### Totem & Mobile (Autenticação via `access` middleware)
- `GET /api/check-queue`: Verifica se há alguma pesagem em andamento.
- `POST /api/start-weighing/{id}`: Inicia o processo de pesagem para um usuário.
- `POST /api/finish-weighing/{id}`: Finaliza a pesagem.
- `GET /api/check-results/{id}`: Obtém os resultados da participação.

### API Externa (Autenticação via `token` middleware)
- `GET /api/check-credits`: Verifica os créditos de um usuário.
- `POST /api/redeem`: Resgata produtos com os créditos.
- `GET /api/get-all-products`: Lista todos os produtos disponíveis.

## Licença

Distribuído sob a licença MIT. Veja `LICENSE` para mais informações.
