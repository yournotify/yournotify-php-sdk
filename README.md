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

$apiKey = 'your_api_key_here';
$yournotify = new Yournotify($apiKey);
```

## Available Methods

### Sending an Email

```php
$response = $yournotify->sendEmail('Title', 'Subject', '<h1>Hello</h1>', 'Hello', 'running', 'sender@example.com', 'recipient@example.com', 'Name', ['key' => 'value']);
print_r($response);
```

### Sending an SMS

```php
$response = $yournotify->sendSMS('Title', 'Subject', 'Hello', 'running', 'SENDER_ID', '+2348100000000', 'Name', ['key' => 'value']);
print_r($response);
```

### Adding a Contact

```php
$response = $yournotify->addContact('email@example.com', '+2348100000000', 'list_id', 'Contact Name', ['key' => 'value']);
print_r($response);
```

### Getting All Contacts

```php
$response = $yournotify->getContacts();
print_r($response);
```

### Deleting a Contact

```php
$response = $yournotify->deleteContact(123);
print_r($response);
```

### Adding a List

```php
$response = $yournotify->addList('Title', 'public', 'single');
print_r($response);
```

### Getting All Lists

```php
$response = $yournotify->getLists();
print_r($response);
```

### Deleting a List

```php
$response = $yournotify->deleteList(456);
print_r($response);
```

### Deleting a Campaign

```php
$response = $yournotify->deleteCampaign(789);
print_r($response);
```

### Getting Campaign Stats

```php
$response = $yournotify->getCampaignStats(123, 'email');
print_r($response);
```

### Getting Campaign Reports

```php
$response = $yournotify->getCampaignReports(123, 'sms');
print_r($response);
```

## API Methods Reference

-   `sendEmail($title, $subject, $html, $text, $status, $from, $to, $name, $attribs)`: Sends an email.
-   `sendSMS($title, $subject, $text, $status, $from, $to, $name, $attribs)`: Sends an SMS.
-   `addContact($email, $telephone, $list, $name, $attribs)`: Adds a contact to a list.
-   `getContacts()`: Retrieves all contacts.
-   `deleteContact($id)`: Deletes a contact by ID.
-   `addList($title, $type, $optin)`: Creates a new list.
-   `getLists()`: Retrieves all lists.
-   `deleteList($id)`: Deletes a list by ID.
-   `deleteCampaign($id)`: Deletes a campaign by ID.
-   `getCampaignStats($ids, $channel)`: Retrieves campaign statistics.
-   `getCampaignReports($ids, $channel)`: Retrieves campaign reports.

## More Information

For full API reference, visit the [Yournotify API Documentation](https://api.yournotify.com/doc).

## License

This SDK is open-source and available under the MIT License.
