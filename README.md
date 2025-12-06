# Flash Sale Application

A high-performance flash sale system built with Laravel, featuring product holds, order processing, and payment integration.

## Features

- **User Authentication**
  - Registration and login with JWT
  - Secure API endpoints with Sanctum

- **Product Management**
  - View product details
  - Real-time stock updates

- **Flash Sale System**
  - Concurrent order processing
  - Product hold mechanism
  - Order processing with payment integration

- **Payment Processing**
  - Webhook for payment notifications
  - Idempotent operation support

## API Endpoints

### Authentication
- `POST /api/register` - Register a new user
- `POST /api/login` - Authenticate user
- `POST /api/logout` - Invalidate user session

### Products
- `GET /api/products/{id}` - Get product details

### Holds
- `POST /api/holds` - Place a hold on a product

### Orders
- `POST /api/orders` - Create a new order

### Webhooks
- `POST /api/payments/webhook` - Handle payment notifications

## Database Schema

### Products
- id (bigint)
- name (string)
- description (text)
- price (decimal)
- stock (integer)
- created_at (timestamp)
- updated_at (timestamp)

### Holds
- id (bigint)
- product_id (foreign key)
- user_id (foreign key)
- expires_at (timestamp)
- created_at (timestamp)
- updated_at (timestamp)

### Orders
- id (bigint)
- user_id (foreign key)
- product_id (foreign key)
- status (string)
- amount (decimal)
- payment_reference (string)
- created_at (timestamp)
- updated_at (timestamp)

### Users
- id (bigint)
- name (string)
- email (string, unique)
- password (hashed)
- created_at (timestamp)
- updated_at (timestamp)

## Testing

Run the test suite:

```bash
php artisan test
```

### Test Coverage
- Concurrency testing
- Hold management
- Order processing
- Payment webhook handling
- Product management

## Environment Variables

Copy `.env.example` to `.env` and configure:

```env
APP_NAME=FlashSale
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=flashsale
DB_USERNAME=root
DB_PASSWORD=

PAYMENT_PROVIDER_URL=
PAYMENT_WEBHOOK_SECRET=
```

## Installation

1. Clone the repository
2. Install dependencies:
   ```bash
   composer install
   npm install
   ```
3. Generate application key:
   ```bash
   php artisan key:generate
   ```
4. Run migrations:
   ```bash
   php artisan migrate
   ```
5. Start the development server:
   ```bash
   php artisan serve
   ```

## Security

- Uses Laravel's built-in CSRF protection
- Password hashing with bcrypt
- API rate limiting
- Input validation
- Secure session handling

## Contributing

1. Fork the repository
2. Create your feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add some amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

## License

This project is open-sourced software licensed under the [MIT license](https://opensource.org/licenses/MIT).