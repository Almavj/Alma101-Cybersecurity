# Backend Setup Instructions

## Prerequisites
1. Install PHP 8.0 or higher
2. Install MongoDB
3. Install Composer

## Installation Steps

1. Navigate to the backend directory:
```bash
cd backend
```

2. Install PHP dependencies:
```bash
composer install
```

3. Configure environment variables:
- Copy `.env.example` to `.env`
- Update the MongoDB connection string and other settings

4. Initialize MongoDB:
```bash
mongosh your_database_name config/init-mongo.js
```

5. Set up your web server (Apache/Nginx) to point to the backend directory

## API Endpoints

### Authentication
- POST /api/login.php - Login
- POST /api/register.php - Register new user

### Blogs
- GET /api/blogs.php - Get all blogs
- GET /api/blogs.php?id={id} - Get single blog
- POST /api/blogs.php - Create new blog
- PUT /api/blogs.php?id={id} - Update blog
- DELETE /api/blogs.php?id={id} - Delete blog

### Videos
- GET /api/videos.php - Get all videos
- GET /api/videos.php?id={id} - Get single video
- POST /api/videos.php - Create new video
- PUT /api/videos.php?id={id} - Update video
- DELETE /api/videos.php?id={id} - Delete video

### Tools
- GET /api/tools.php - Get all tools
- GET /api/tools.php?id={id} - Get single tool
- POST /api/tools.php - Create new tool
- PUT /api/tools.php?id={id} - Update tool
- DELETE /api/tools.php?id={id} - Delete tool

### Contact
- POST /api/contact.php - Submit contact form

## Security
- All endpoints except login, register, and contact form require JWT authentication
- Tokens expire after 24 hours
- MongoDB validation ensures data integrity
- Password hashing using PHP's password_hash function

## Error Handling
All endpoints return appropriate HTTP status codes:
- 200: Success
- 201: Created
- 400: Bad Request
- 401: Unauthorized
- 403: Forbidden
- 404: Not Found
- 500: Server Error