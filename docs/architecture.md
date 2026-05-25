# Architecture

## Overview

AlgoSpark is built as a classic Symfony web application with one extra long-running process: the submission judge worker. The application is organized around four main layers:

- Presentation layer: Twig templates, shared components, and AJAX modal forms
- HTTP layer: Symfony controllers that handle pages and JSON endpoints
- Domain layer: Doctrine entities and repositories
- Background layer: the CLI judge worker that compiles and runs submissions

## Main request flow

1. A browser opens the homepage or a problem page.
2. A controller loads data through Doctrine repositories and renders Twig templates.
3. If the user submits code, the submission controller stores the source file in storage/submission_files and creates a Submission row.
4. The worker polls for submissions with no verdict, loads the source file, compiles or runs it, compares the output against the test cases, and writes the verdict back to the database.
5. The frontend refreshes submission and profile views through AJAX endpoints.

## Authentication flow

Authentication is modal-based rather than page-based.

- The login modal posts to /auth/login.
- The signup modal posts to /auth/register.
- AuthController validates credentials or creates a new user.
- LoginFormAuthenticator is used to establish the Symfony session.
- LoginFormAuthenticator is used to establish the Symfony session.

## Submission and judging flow

- SubmissionController receives the code and optional uploaded file.
- The source is written to storage/submission_files/{userId}/{submissionId}.{ext}.
- The worker copies the source into a temporary work directory.
- For Java, the worker detects the class name and package, writes the source to the matching file path, and runs the fully qualified class name.
- The worker loads the verdict rows, fetches the test cases, compiles if needed, runs each test, and normalizes the output before comparing it.
- The final verdict and execution metrics are flushed back to the Submission entity.

## Domain model

- User: account data, rating, bio, avatar, and role state
- Problem: title, description, difficulty, category, limits, and acceptance stats
- TestCase: input, expected output, and sample flag
- Submission: source language, verdict, timing, memory, and pass counts
- VerdictStatus: displayed verdict label and color
- Language: compiler command, file extension, and enabled state
- UserFavorite: relationship between two users

## Infrastructure

- Doctrine migrations define the schema
- MySQL is the configured database engine

- Asset Mapper serves the frontend JavaScript and CSS bundles

- Twig bundles common layouts and modals into base.html.twig
