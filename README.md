# Yournotify PHP SDK

## Installation

To install the Yournotify PHP SDK, you can use Composer. Run the following command in your terminal:

```bash
composer require yournotify/yournotify-php-sdk
```

## Usage

To use the Yournotify SDK, you need to include the autoload file and create an instance of the `Yournotify` class with your API key.

```php
require 'vendor/autoload.php';

use Yournotify\Yournotify;

$apiKey = 'your_api_key_here';
$yournotify = new Yournotify($apiKey);
```

### Sending an Email

To send an email, use the `sendEmail` method:

```php
$response = $yournotify->sendEmail('Title', 'Subject', '<h1>Hello</h1>', 'Hello', 'running', 'sender@example.com', 'recipient@example.com', 'Name', 'object{key => value}');
```

### Sending an SMS

To send an SMS, use the `sendSMS` method:

```php
$response = $yournotify->sendSMS('Title', 'sender_id', 'Hello', 'running', '+2348100000000', 'Name', 'object{key => value}');
```

### Adding a Contact

To add a contact, use the `addContact` method:

```php
$response = $yournotify->addContact('email@example.com', '1234567890', 'list_id', 'Contact Name');
```

### Getting Contacts

To retrieve all contacts, use the `getContacts` method:

```php
$response = $yournotify->getContacts();
```

### Adding a List

To add a new list, use the `addList` method:

```php
$response = $yournotify->addList('List Title');
```

## API Methods

-   `sendEmail($to, $subject, $html, $text, $from)`: Sends an email.
-   `sendSMS($to, $message, $from)`: Sends an SMS.
-   `addContact($email, $telephone, $list, $name)`: Adds a contact to a list.
-   `getContacts()`: Retrieves all contacts.
-   `addList($title)`: Creates a new list.

For more information, visit the [YourNotify API documentation](https://api.yournotify.com/doc).

## License

This SDK is open-source and available under the MIT License.
