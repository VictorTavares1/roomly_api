# Roomly API — Backend REST

<p align="center">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4?style=flat-square&logo=php&logoColor=white" />
  <img src="https://img.shields.io/badge/MySQL-8.x-4479A1?style=flat-square&logo=mysql&logoColor=white" />
  <img src="https://img.shields.io/badge/XAMPP-Apache-FB7A24?style=flat-square&logo=apache&logoColor=white" />
  <img src="https://img.shields.io/badge/status-estável-brightgreen?style=flat-square" />
  <img src="https://img.shields.io/badge/versão-1.0.0-blue?style=flat-square" />
</p>

API REST desenvolvida em PHP para o sistema **Roomly** — plataforma de gestão e reserva de salas escolares. Responsável por toda a lógica de negócio, autenticação, controlo de acessos e comunicação com a base de dados MySQL.

> **Repositório do Frontend:** [github.com/VictorTavares1/roomly](https://github.com/VictorTavares1/roomly)

---

## Índice

- [Sobre a API](#sobre-a-api)
- [Funcionalidades](#funcionalidades)
- [Stack Tecnológica](#stack-tecnológica)
- [Pré-requisitos](#pré-requisitos)
- [Instalação e Configuração](#instalação-e-configuração)
- [Estrutura do Projeto](#estrutura-do-projeto)
- [Endpoints](#endpoints)
- [Segurança](#segurança)
- [Base de Dados](#base-de-dados)
- [Autor](#autor)

---

## Sobre a API

A API do Roomly segue uma arquitetura **REST** organizada por recursos, na qual cada endpoint é responsável por uma operação específica. Toda a comunicação é feita em **JSON** e todos os pedidos autenticados exigem um token de sessão válido no cabeçalho HTTP.

A API é o único ponto de acesso à base de dados — o frontend nunca comunica diretamente com o MySQL, garantindo segurança e centralização da lógica de negócio.

---

## Funcionalidades

- Autenticação baseada em **tokens de 64 caracteres** com data de expiração
- Hashing de palavras-passe com **bcrypt**
- **Rate limiting** — bloqueio automático após 5 tentativas de login falhadas em 15 minutos
- Controlo de acessos por **perfil** (professor, funcionário, administrador)
- Gestão completa de salas, reservas, utilizadores e relatórios
- Verificação de **conflitos de reservas** antes da criação
- Sistema de **check-in por QR Code** com validação de reserva ativa
- Integração com a **Groq API** para o assistente virtual com IA
- **Registo de atividade** automático de todas as ações relevantes na plataforma
- Proteção contra **SQL Injection** via PDO com Prepared Statements

---

## Stack Tecnológica

| Tecnologia | Função |
|------------|--------|
| **PHP 8+** | Linguagem principal da API |
| **MySQL 8** | Base de dados relacional |
| **Apache** | Servidor web (via XAMPP) |
| **PDO** | Acesso seguro à base de dados |
| **bcrypt** | Hashing de palavras-passe |
| **Groq API** | Serviço externo de IA (LLaMA) |

---

## Pré-requisitos

| Requisito | Versão Mínima |
|-----------|---------------|
| **XAMPP** | 8.x |
| **PHP** | 8.0+ |
| **MySQL** | 8.0+ |

---

## Instalação e Configuração

### 1. Clonar o Repositório

```bash
git clone https://github.com/VictorTavares1/roomly_api.git
```

### 2. Copiar para o XAMPP

Copiar a pasta `roomly_api` para o diretório `htdocs` do XAMPP:

```
C:\xampp\htdocs\roomly_api\
```

### 3. Criar a Base de Dados

1. Iniciar o **Apache** e o **MySQL** no painel do XAMPP
2. Abrir o phpMyAdmin em `http://localhost/phpmyadmin`
3. Criar uma base de dados chamada `roomly`
4. Importar o ficheiro `database/roomly.sql`

### 4. Configurar as Variáveis de Ambiente

Criar um ficheiro `.env` na raiz da pasta `roomly_api` com o seguinte conteúdo:

```env
DB_HOST=localhost
DB_NAME=roomly
DB_USER=root
DB_PASS=

GROQ_API_KEY=a_tua_chave_aqui
```

> O ficheiro `.env` está incluído no `.gitignore` e nunca deve ser partilhado ou commitado.

### 5. Verificar a Configuração

Aceder a `http://localhost/roomly_api/api/rooms/list.php` no browser. Se a resposta for JSON, a API está a funcionar corretamente.

---

## Estrutura do Projeto

```
roomly_api/
├── .env                          # Variáveis de ambiente (não commitado)
├── database/
│   └── roomly.sql                # Script SQL de criação da base de dados
│
├── config/
│   ├── db.php                    # Conexão PDO à base de dados
│   ├── cors.php                  # Headers CORS centralizados
│   └── logger.php                # Sistema de logging de erros
│
└── api/
    ├── auth/
    │   ├── login.php             # POST — Autenticação e geração de token
    │   ├── logout.php            # POST — Invalidação do token
    │   ├── update_password.php   # POST — Alteração de palavra-passe
    │   └── update_profile.php    # POST — Atualização de perfil
    │
    ├── rooms/
    │   ├── list.php              # GET — Listar salas
    │   ├── create.php            # POST — Criar sala
    │   ├── update.php            # POST — Atualizar sala
    │   └── delete.php            # POST — Eliminar sala
    │
    ├── reservations/
    │   ├── list_all.php          # GET — Todas as reservas
    │   ├── list_my.php           # GET — Reservas do utilizador autenticado
    │   ├── list_by_date.php      # GET — Reservas por data
    │   ├── create.php            # POST — Criar reserva (com verificação de conflitos)
    │   ├── update.php            # POST — Atualizar reserva
    │   ├── delete.php            # POST — Cancelar reserva
    │   ├── admin_delete.php      # POST — Eliminar reserva (admin)
    │   └── checkin.php           # POST — Check-in por QR Code
    │
    ├── reports/
    │   ├── list.php              # GET — Listar relatórios
    │   ├── create.php            # POST — Criar relatório de ocorrência
    │   ├── update_status.php     # POST — Atualizar estado do relatório
    │   └── dashboard_stats.php   # GET — Estatísticas do dashboard
    │
    ├── users/
    │   ├── list.php              # GET — Listar utilizadores
    │   ├── create.php            # POST — Criar utilizador
    │   ├── update_status.php     # POST — Ativar/desativar conta
    │   └── update_role.php       # POST — Alterar perfil
    │
    ├── activity/
    │   └── list.php              # GET — Registos de atividade (admin)
    │
    └── ai/
        └── chat.php              # POST — Assistente virtual (Groq API)
```

---

## Endpoints

Todos os endpoints requerem o header `Authorization: Bearer {token}`, exceto o login.

### Autenticação

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `POST` | `/api/auth/login.php` | Autenticação — devolve token |
| `POST` | `/api/auth/logout.php` | Invalidar token de sessão |
| `POST` | `/api/auth/update_password.php` | Alterar palavra-passe |
| `POST` | `/api/auth/update_profile.php` | Atualizar dados do perfil |

### Salas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/rooms/list.php` | Listar todas as salas |
| `POST` | `/api/rooms/create.php` | Criar sala (admin) |
| `POST` | `/api/rooms/update.php` | Atualizar sala (admin) |
| `POST` | `/api/rooms/delete.php` | Eliminar sala (admin) |

### Reservas

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/reservations/list_all.php` | Listar todas as reservas |
| `GET` | `/api/reservations/list_my.php` | Reservas do utilizador autenticado |
| `GET` | `/api/reservations/list_by_date.php?date=YYYY-MM-DD` | Reservas por data |
| `POST` | `/api/reservations/create.php` | Criar reserva |
| `POST` | `/api/reservations/update.php` | Atualizar reserva |
| `POST` | `/api/reservations/delete.php` | Cancelar reserva |
| `POST` | `/api/reservations/checkin.php` | Check-in por QR Code |

### Relatórios

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/reports/list.php` | Listar relatórios |
| `POST` | `/api/reports/create.php` | Criar relatório de ocorrência |
| `POST` | `/api/reports/update_status.php` | Atualizar estado |
| `GET` | `/api/reports/dashboard_stats.php` | Estatísticas do dashboard |

### Utilizadores

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/users/list.php` | Listar utilizadores (admin) |
| `POST` | `/api/users/create.php` | Criar utilizador (admin) |
| `POST` | `/api/users/update_status.php` | Ativar/desativar conta (admin) |
| `POST` | `/api/users/update_role.php` | Alterar perfil (admin) |

### Assistente Virtual

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `POST` | `/api/ai/chat.php` | Enviar mensagem ao assistente IA |

### Atividade

| Método | Endpoint | Descrição |
|--------|----------|-----------|
| `GET` | `/api/activity/list.php` | Listar registos de atividade (admin) |

---

## Segurança

- **SQL Injection** — todos os pedidos utilizam PDO com Prepared Statements
- **Palavras-passe** — armazenadas com `password_hash()` (bcrypt), nunca em texto simples
- **Tokens** — gerados com `bin2hex(random_bytes(32))`, 64 caracteres, com data de expiração
- **Rate Limiting** — bloqueio após 5 tentativas falhadas em 15 minutos (tabela `login_attempts`)
- **CORS** — configuração centralizada em `config/cors.php`
- **Variáveis de ambiente** — chaves e credenciais armazenadas em `.env`, excluído do git

---

## Base de Dados

A base de dados `roomly` é composta por 6 tabelas:

| Tabela | Descrição |
|--------|-----------|
| `users` | Utilizadores registados na plataforma |
| `rooms` | Salas disponíveis para reserva |
| `reservations` | Reservas efetuadas pelos utilizadores |
| `reports` | Relatórios de ocorrências técnicas |
| `activity_logs` | Registo de atividade de todos os utilizadores |
| `login_attempts` | Tentativas de autenticação (rate limiting) |

O script de criação completo encontra-se em `database/roomly.sql`.

---

## Autor

**Victor Tavares** — Desenvolvimento Full Stack

- Frontend: [github.com/VictorTavares1/roomly](https://github.com/VictorTavares1/roomly)
- Backend: [github.com/VictorTavares1/roomly_api](https://github.com/VictorTavares1/roomly_api)

---

<p align="center">
  <strong>Roomly</strong> · Plataforma de Gestão de Salas Escolares · © 2026
</p>
