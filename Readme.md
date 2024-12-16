
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

## Key Features

- **JWT Authentication**:
  - Secure access to the API using JSON Web Tokens (JWT). Each request requires a valid token in the `Authorization` header.
  
- **Rate Limiting**:
  - Prevents abuse by limiting the number of API requests a client can make in a given time period.
  
- **Pagination**:
  - Supports pagination to fetch results in chunks. This improves performance and provides a better user experience for large datasets.

- **Spatial Index for Geolocation**:
  - Efficiently queries nearby alumni using geospatial data stored in a `POINT` column with a spatial index.
  
- **Haversine Formula**:
  - Calculates distances between geospatial points to identify alumni within a given radius.
  
- **Validation and Error Handling**:
  - Comprehensive input validation and user-friendly error messages for invalid requests.

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

```
URL: http://localhost/generateToken.php
Method: POST

Request Payload:

{
    "user_id": 1
}

Response:

{
    "status": "success",
    "token": "eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJpc3MiOiJhbHVtbmlfbG9jYXRvciIsImlhdCI6MTczNDMzNzY4NSwiZXhwIjoxNzM0MzQxMjg1LCJ1c2VyX2lkIjoxfQ.gw5TIgeQZ7T_PV4Mld31c9YB9a8AFJKSH7jfSJQ6wDw"
}
```

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
GET /getNearbyAlumni.php?user_id=1&radius=2
Authorization: Bearer <your-jwt-token>
```

### Example Response:
```json
{
    "status": "success",
    "data": [
        {
            "id": 2,
            "name": "Bob White",
            "email": "bob.white@example.com",
            "location": "POINT(37.7849 -122.4194)",
            "distance": 0.596785048070618
        },
        {
            "id": 4,
            "name": "Diana Blue",
            "email": "diana.blue@example.com",
            "location": "POINT(37.7649 -122.4194)",
            "distance": 0.596785048070618
        },
        {
            "id": 5,
            "name": "Eve Brown",
            "email": "eve.brown@example.com",
            "location": "POINT(37.7649 -122.4094)",
            "distance": 1.2630139282833803
        }
    ],
    "pagination": {
        "total_records": 3,
        "total_pages": 1,
        "current_page": 1,
        "limit": 10
    }
}
```

![Alumni Locator Screenshot](images/getNearbyAlumni.png)

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
    "id": 1,
    "name": "Alice Green",
    "email": "alice.green1@example.com",
    "latitude": "37.7749",
    "longitude": "-122.4194"
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

![Alumni Locator Screenshot](images/updateUser.png)

---

## Rate Limiting

Rate limiting is implemented for these APIs to prevent abuse and ensure fair usage. You can set the number of requests allowed per minute/hour, and any requests exceeding this limit will be rejected with a 429 status code (Too Many Requests).

---

## Error Codes

- **400 Bad Request**: Invalid or missing parameters in the request.
- **401 Unauthorized**: Invalid or missing JWT token.
- **403 Forbidden**: User is not authorized to access the resource.
- **404 Not Found**: The resource could not be found (e.g., user does not exist).
- **500 Internal Server Error**: A general error occurred on the server.
- **429 Too Many Requests**: Rate limit exceeded.
