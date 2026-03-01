# Roomly - Sistema de Gestão de Reservas de Salas de Aula

![Status do Projeto](https://img.shields.io/badge/status-em_desenvolvimento-orange)
![Versão](https://img.shields.io/badge/versão-1.0.0-blue)
![Licença](https://img.shields.io/badge/licença-MIT-green)

O **Roomly** é uma solução moderna e eficiente para a gestão de espaços escolares. Este sistema permite que professores e administradores reservem salas, giram horários e consultem a disponibilidade de espaços em tempo real, eliminando conflitos de agendamento e o uso de papel.

---

## 🚀 Tecnologias Utilizadas

Este projeto foi desenvolvido com uma arquitetura separada (Headless), garantindo escalabilidade e facilidade de manutenção.

### Backend (API)
*   **Linguagem:** PHP 8+ (Vanilla)
*   **Base de Dados:** MySQL / MariaDB
*   **Servidor Web:** Apache (via XAMPP)
*   **Segurança:** PDO (Prepared Statements), Passwords: Hashing (Bcrypt), CORS Centralizado.
*   **Arquitetura:** RESTful API organizada por recursos.

### Frontend (Interface)
*   **Framework:** React.js
*   **Estilização:** Tailwind CSS (a confirmar)
*   **Comunicação:** Fetch API (Serviços modularizados)
*   **Gestão de Estado:** React Hooks & Context API

---

## 📂 Estrutura do Projeto

A API foi reestruturada para seguir boas práticas de engenharia de software, separando configurações, autenticação e recursos.

```
/roomly_api
│
├── /config           # Configurações globais (Base de Dados, CORS)
│   ├── db.php        # Conexão segura e centralizada à BD
│   └── cors.php      # Headers de controlo de acesso
│
├── /api              # Endpoints da API
│   ├── /auth         # Autenticação (Login, Passwords, Perfil)
│   ├── /users        # Gestão de Utilizadores (CRUD)
│   ├── /rooms        # Gestão de Salas
│   ├── /reservations # Sistema de Reservas e Calendário
│   └── /reports      # Relatórios e Estatísticas
```

---

## 🛠️ Instalação e Configuração

### Pré-requisitos
*   **XAMPP** (ou similar) instalado para correr PHP e MySQL.
*   **Node.js** instalado para o frontend React.

### 1. Configurar o Backend (API)
1.  Clone ou copie a pasta `roomly_api` para o diretório `htdocs` do XAMPP (`C:\xampp\htdocs\roomly_api`).
2.  Inicie o **Apache** e o **MySQL** no painel do XAMPP.
3.  Importe a base de dados:
    *   Abra o phpMyAdmin (`http://localhost/phpmyadmin`).
    *   Crie uma base de dados chamada `roomly`.
    *   Importe o ficheiro SQL fornecido (ex: `roomly.sql`).
4.  Configure as credenciais em `config/db.php` se necessário.

### 2. Configurar o Frontend
1.  Navegue até à pasta do frontend (ex: `Documents/Roomly`).
2.  Instale as dependências:
    ```bash
    npm install
    ```
3.  Inicie o servidor de desenvolvimento:
    ```bash
    npm run dev
    ```
4.  Aceda à aplicação no browser (geralmente em `http://localhost:5173`).

---

## 🔌 Documentação da API

Aqui estão alguns dos principais endpoints disponíveis:

| Método | Endpoint                    | Descrição                          |
| :----- | :-------------------------- | :--------------------------------- |
| POST   | `/api/auth/login.php`       | Autenticação de utilizador         |
| GET    | `/api/rooms/list.php`       | Lista todas as salas ativas        |
| GET    | `/api/reservations/list_all.php` | Lista todas as reservas       |
| POST   | `/api/reservations/create.php`   | Cria uma nova reserva         |
| GET    | `/api/reports/dashboard_stats.php` | Estatísticas para o Dashboard|

---

## 🔒 Segurança

*   **Proteção contra SQL Injection:** Uso rigoroso de Prepared Statements.
*   **Encriptação de Senhas:** Nenhuma senha é guardada em texto limpo; utiliza-se `password_hash()`.
*   **CORS:** Configuração centralizada para permitir apenas pedidos autorizados.

---

## 👤 Autores

*   **Victor Tavares** - *Desenvolvimento Full Stack*

---


