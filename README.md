# laravel-microservice-kakfa
A scalable microservice architecture built with Laravel, leveraging Apache Kafka for real-time event streaming and message handling. This repository demonstrates the implementation of a decoupled system for efficient communication between services.


# Microservices with Docker and Kafka

This project demonstrates the implementation of two microservices using Docker and Kafka. The Auth service handles user registration and authentication, while the Products service manages product addition and listing. The Products service integrates with a managed Kafka instance (powered by Aiven) to publish and consume messages for reliable and scalable event-driven architecture.

---

## Prerequisites

1. *Docker*: Ensure Docker is installed and running on your system.
2. *Kafka*: This project uses Aiven’s managed Kafka, accessible via REST API.
3. *Postman or API Client*: For testing API endpoints.

---

## Microservices Overview

### 1. *Auth Service*
- Handles user registration, login, logout, and user details.
- Provides authentication and session management.

### 2. *Products Service*
- Manages product addition and listing.
- Uses Kafka to publish messages when a product is added.
- Consumes Kafka messages to list products in real-time.

---

## Endpoints

### *Auth Service*
1. *Register User*  
   POST http://localhost/auth/api/auth/register
   
2. *Login User*  
   POST http://localhost/auth/api/auth/login

3. *Logout User*  
   POST http://localhost/auth/api/auth/logout

4. *Get User Details*  
   GET http://localhost/auth/api/auth/me

### *Products Service*
1. *Add Product*  
   POST http://localhost/products/api/products

2. *List Products*  
   GET http://localhost/products/api/products

---

## Key Features

1. *Event-Driven Architecture*
   - Products are published to a Kafka topic upon addition.
   - Kafka ensures reliable delivery and real-time processing.

2. *Microservice Separation*
   - Independent services for Auth and Products.
   - Scalable and maintainable architecture.

3. *Managed Kafka*
   - Aiven’s Kafka is used to simplify configuration and scalability.
   - Integration with REST API for message publishing and consumption.

---

## Steps to Run

1. Clone the repository:
   bash
   git clone https://github.com/your-repo.git
   cd your-repo
   

2. Start Docker containers:
   bash
   docker compose up -d
   

3. Verify running containers:
   bash
   docker ps
   

4. Access the services:
   - *Auth Service*: http://localhost/auth
   - *Products Service*: http://localhost/products

---

## Testing the Endpoints

### *Auth Service*
- Register User:
  bash
  curl -X POST http://localhost/auth/api/auth/register -H "Content-Type: application/json" -d '{"name": "John Doe", "email": "john@example.com", "password": "password"}'
  

- Login User:
  bash
  curl -X POST http://localhost/auth/api/auth/login -H "Content-Type: application/json" -d '{"email": "john@example.com", "password": "password"}'
  

- Logout User:
  bash
  curl -X POST http://localhost/auth/api/auth/logout -H "Authorization: Bearer <your-token>"
  

- Get User Details:
  bash
  curl -X GET http://localhost/auth/api/auth/me -H "Authorization: Bearer <your-token>"
  

### *Products Service*
- Add Product:
  bash
  curl -X POST http://localhost/products/api/products -H "Content-Type: application/json" -d '{"name": "Product 1", "description": "Description of Product 1", "price": 100}'
  

- List Products:
  bash
  curl -X GET http://localhost/products/api/products
  

---


---

## Tools & Technologies Used

1. *Docker*: Containerized services for seamless deployment.
2. *Laravel*: PHP framework for building robust APIs.
3. *Aiven Kafka*: Managed Kafka service for reliable messaging.
4. *REST API*: Used for Kafka message publishing and consumption.

---
