# Getting Started

To get started with this project, follow these steps:

1. Clone the repository using the following command:
2. Navigate to the main branch of the repository: ```git checkout main```
3. Up containers:  ```docker-compose up```
4. Install project dependencies using Compose: ```composer install```
5. Configure the `.env`
6. Migrate: ```docker exec -it  php php artisan migrate:fresh```
7. Go to ```http://localhost/docs/api```

## Using xml parser command

1. Use: ```docker exec -it  php php artisan xml:parse {"your path to xml"}```



