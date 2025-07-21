# E-commerce Backend

This is the backend for the e-commerce platform, providing all the necessary APIs for managing products, orders, users, and more.

## Requirements

Before you begin, ensure you have met the following requirements:

*   **PHP:** 8.0 or higher
*   **Database:** MySQL 8.0+ or PostgreSQL 12+
*   **Web Server:** Nginx or Apache
*   **Composer:** [Latest version](https://getcomposer.org/)
*   **PHP Extensions:**
    *   `pdo_mysql` or `pdo_pgsql`
    *   `mbstring`
    *   `openssl`
    *   `json`
    *   `bcmath`
    *   `ctype`
    *   `xml`

## Installation Steps

Follow these steps to get your development environment set up:

1.  **Clone the repository:**
    ```bash
    git clone <your-repository-url> ecommerce-backend
    cd ecommerce-backend
    ```

2.  **Install dependencies:**
    ```bash
    composer install
    ```

3.  **Configure your environment:**
    Copy the example environment file and update it with your local configuration (database credentials, etc.).
    ```bash
    cp .env.example .env
    ```
    Then, open `.env` and edit the database connection details.

4.  **Generate application key:**
    *(This assumes a Laravel-based project. If not, this step might differ.)*
    ```bash
    php artisan key:generate
    ```

5.  **Run database migrations:**
    This will create all the necessary tables in your database.
    ```bash
    php artisan migrate
    ```

6.  **Serve the application:**
    ```bash
    php artisan serve
    ```
    The application will be available at `http://127.0.0.1:8000`.

## API Documentation (Swagger)

The API is documented using Swagger/OpenAPI. You can access the interactive API documentation here:

> **Swagger UI:** http://127.0.0.1:8000/api/documentation

This link will be active once you have the application running. It provides a complete overview of all available endpoints, their parameters, and allows you to test them directly from your browser.

## Running Scheduled Tasks Manually

The application includes scheduled tasks that run in the background. For example, concluding auctions is handled by a command that is scheduled to run every minute.

### Concluding Auctions

You can manually trigger the auction conclusion process to test its functionality without waiting for the scheduler.

1.  **Ensure you have an ended auction:** Create a product of type `auction` and set its `auction_end_time` to a time in the past. Place one or more bids on this product to ensure there is a winner.

2.  **Run the command:**
    ```bash
    php artisan auctions:conclude
    ```

3.  **Verify the outcome:**
    *   The command will output information about the auctions it is processing.
    *   Check your `orders` database table for a new order with a `pending_payment` status for the winning bidder.
    *   The `products` table should now have the `auction_concluded_at` timestamp set for the processed auction, preventing it from being processed again.