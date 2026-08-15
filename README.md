# BookIt

## A hotel booking manager made using PHP and MySQL

A hotel booking manager for keeping track of guests, rooms and services, built with plain PHP and MySQL. This app contains:

1. CRUD for Bookings, Rooms, and Services tables
2. Automatic total price calculation from the selected room and service
3. Protection against deleting rooms or services that are still in use by bookings

## How it works
- Plain PHP with no framework, each page is a self-posting route with a hidden action router
- Every query that touches user input uses prepared statements, and all output is escaped
- Foreign keys enforce referential integrity, deleting an in-use room or service is refused
- Edit mode switches the form through the ?id= URL parameter

## Screenshots

## How to run
1. Install XAMPP and start the Apache and MySQL services
2. Create the database and tables by running schema.sql, either in the MySQL CLI or phpMyAdmin
3. From the project folder run:

```bash
php -S localhost:8000
```

4. Open http://localhost:8000

The connection in db.php uses root with no password, the XAMPP default for local development.

## License
This project is licensed under the [MIT License](LICENSE)
