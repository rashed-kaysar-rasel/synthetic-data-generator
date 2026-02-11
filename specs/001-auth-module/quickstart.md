# Quickstart: Authentication Module

## Goal
Enable registration, login, logout, and password reset while keeping data generation available to guests.

## Prerequisites
- App running locally.

## Steps
1. Visit the registration page (`/register`) and create a new account with name, email, and password.
2. Log in with the newly created credentials.
3. Log out and confirm you are returned to a logged-out state.
4. Use the password reset flow (`/password/forgot`) to request and set a new password.
5. Visit your profile page (`/profile`) while logged in and update your name or password.
6. As a guest (logged out), access the generator and run a data generation job.

## Expected Results
- Users can register, log in, log out, and reset their password successfully.
- Guest users can generate data without being required to log in.
