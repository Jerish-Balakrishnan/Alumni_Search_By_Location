
# Alumni Locator API

This repository contains two key APIs for managing user profiles and retrieving nearby alumni based on location. The APIs include functionality for:

- **Getting nearby alumni by location** (`/getNearbyAlumni.php`)
- **Updating user details** (`/updateUser.php`)

## Table of Contents

1. [API Overview](#api-overview)
2. [Authentication](#authentication)
3. [1. `/getNearbyAlumni.php`](#1-getnearbyalumniphp)
4. [2. `/updateUser.php`](#2-updateuserphp)
5. [Rate Limiting](#rate-limiting)
6. [Error Codes](#error-codes)

---

## API Overview

This API allows users to:
1. **Get a list of nearby alumni** within a specified radius based on location.
2. **Update user profile details**, including name, email, and location (latitude and longitude).

---

## Authentication

All API endpoints require JWT (JSON Web Token) authentication. You must send the token in the `Authorization` header as a `Bearer` token.

### Example of Authorization Header:
```
Authorization: Bearer <your-jwt-token>
```

### How to generate a JWT token:
- Use the `/generateToken.php` endpoint (or equivalent) to get a JWT for valid users.
- The token will be required for every request.

URL: http://localhost/generateToken.php
Method: POST

Request Payload:

{
    "user_id": 1
}

![Alumni Locator Screenshot](images/generateToken.png)

---

## 1. `/getNearbyAlumni.php`

This API retrieves a list of nearby alumni based on the provided user ID and radius.

### Endpoint
```
GET /getNearbyAlumni.php
```

### Query Parameters:
- `user_id` (required): The ID of the user for whom you are finding nearby alumni.
- `radius` (optional, default is `10`): The radius in kilometers within which alumni are searched. (e.g., `10`, `50`, etc.)

### Example Request:
```
GET /getNearbyAlumni.php?user_id=123&radius=20
Authorization: Bearer <your-jwt-token>
```

### Example Response:
```json
{
  "status": "success",
  "data": [
    {
      "id": 456,
      "name": "John Doe",
      "email": "john.doe@example.com",
      "location": "POINT(10.12345 20.12345)",
      "distance": 5.5
    },
    {
      "id": 789,
      "name": "Jane Doe",
      "email": "jane.doe@example.com",
      "location": "POINT(11.54321 20.56789)",
      "distance": 7.0
    }
  ]
}
```

---

## 2. `/updateUser.php`

This API updates a user's details, including name, email, and location (latitude and longitude).

### Endpoint
```
PUT /updateUser.php
```

### Request Body (JSON):
```json
{
  "id": 123,
  "name": "John Doe",
  "email": "john.doe@example.com",
  "latitude": 10.12345,
  "longitude": 20.12345
}
```

### Example Request:
```
PUT /updateUser.php
Authorization: Bearer <your-jwt-token>
```

### Example Response:
```json
{
  "status": "success",
  "message": "User updated successfully"
}
```

---

## Rate Limiting

Rate limiting can be implemented for these APIs to prevent abuse and ensure fair usage. You can set the number of requests allowed per minute/hour, and any requests exceeding this limit will be rejected with a 429 status code (Too Many Requests).

---

## Error Codes

- **400 Bad Request**: Invalid or missing parameters in the request.
- **401 Unauthorized**: Invalid or missing JWT token.
- **403 Forbidden**: User is not authorized to access the resource.
- **404 Not Found**: The resource could not be found (e.g., user does not exist).
- **500 Internal Server Error**: A general error occurred on the server.
- **429 Too Many Requests**: Rate limit exceeded.
