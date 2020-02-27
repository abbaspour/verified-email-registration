Sample App to register with email verification


## Setup
1. Register a RWA client in Auth0
![Register a RWA client in Auth0](img/app-create.png)
2. Configure callback and CORS URLs
![App Settings](img/app-settings.png)
3. Add implicit and M2M grants to your App
![App Grants](img/app-grants.png)
4. Add DB connection to App 
![App DB Connection](img/app-conn-1.png)
5. Add Passwordless Email to App 
![App Passwordless Connection](img/app-conn-2.png)
6. Add Management API with following scopes to your clients:
    `users:create`, `users:delete`
![App M2M Scopes](img/app-m2m-scopes.png)
7. (Optional) [register](https://www.google.com/recaptcha/admin/create) a Google reCAPTCHA v3 account
![Captcha Register](img/captcha-register.png)

8. Copy `env-sample` to `.env` and update client information
9. Copy `env.js-sample` to `env.js` and update client information 


## Running
Clone the project first.

```bash
$ cat /etc/hosts | grep app1.com
127.0.0.1  app1.com

$ compose install

$ php -S app1.com:3001 -e 
```

### Sequence Diagram
![Registration Sequence Diagram](https://www.websequencediagrams.com/files/render?link=aVQ0WropybKb6WlkPtMGXuKcHSgTRntwqXukAiBdvPp9uZxstzy2acpcSJGYkTt6)

### Screenshots  
1. User visit http://app1.com:3001
![Enter Email](img/1-start.png)
![See Prompt](img/1-check-mail.png)

2. Check Mailbox
![Mailbox](img/2-email.png)
  
3. Enter name, password and other details
![Create](img/3-create.png)
  
4. Success
![Success](img/4-success.png)

5. User Profile
![Profile](img/5-profile.png)
  

