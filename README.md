# Payment Processing Project

## Overview

This project is a PHP-based payment processing system built with Symfony. It provides a flexible architecture for handling payments through multiple providers, currently supporting Shift4 and OPPWA (Online Program Payment Web Application).

## Project Structure

The project follows a clean, modular structure:

- `src/Command/`: Contains the Symfony console command for processing payments.
- `src/Controller/`: Houses the web controller for handling payment requests.
- `src/Payment/`: Core payment processing logic.
    - `Builder/`: Contains builder classes for payment clients and responses.
    - `Client/`: Implements specific payment provider clients (Shift4 and OPPWA).
    - `Dto/`: Data Transfer Objects for payment requests and responses.
    - `Interface/`: Defines interfaces for the payment system components.
- `docker/`: Contains Docker-related files for containerization.
- `tests/`: Contains unit and integration tests.

## Key Components

1. `PaymentService`: Orchestrates the payment process.
2. `PaymentClientBuilder`: Builds the appropriate payment client based on the provider.
3. `PaymentResponseBuilder`: Constructs the payment response DTO.
4. `Shift4PaymentProcessor` and `OppwaPaymentProcessor`: Implement provider-specific payment processing logic.
5. `ProcessPaymentCommand`: Symfony console command for processing payments via CLI.
6. `PaymentController`: Handles HTTP requests for payment processing.

## Running the Project

### Prerequisites

- Docker and Docker Compose

### Steps to Run

1. Clone the repository:
   ```
   git clone [repository-url]
   cd [project-directory]
   ```

2. Build and start the Docker containers:
   ```
   docker-compose up -d --build
   ```

3. Install dependencies:
   ```
   docker-compose exec php composer install
   ```

4. The application should now be running at `http://localhost:8080`

## Usage

### Via Web API

Send a POST request to `/app/example/{provider}` where `{provider}` is either `shift4` or `oppwa`.

Example request body:
```json
{
  "amount": 100.00,
  "currency": "USD",
  "cardNumber": "4111111111111111",
  "cardExpMonth": "12",
  "cardExpYear": "2025",
  "cardCvv": "123",
  "customerId": "customer123"
}
```

### Via Command Line

Use the Symfony console command:

```
docker-compose exec php bin/console app:process-payment [amount] [currency] [cardNumber] [cardExpYear] [cardExpMonth] [cardCvv] --provider=[shift4|oppwa]
```

Example:
```
docker-compose exec php bin/console app:process-payment 100.00 USD 4111111111111111 2025 12 123 --provider=shift4
```

## Testing

The project includes both unit and integration tests. To run the tests, use the following command:

```
docker-compose exec php bin/phpunit
```

This will execute all the tests in the `tests/` directory, providing you with a comprehensive test coverage report.

## Note

The current implementation uses mock API responses for demonstration purposes. In a production environment, you would need to replace these with actual API calls to the payment providers.