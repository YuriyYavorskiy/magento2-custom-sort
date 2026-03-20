# Perspective Product Q&A Module (Magento 2)

## Overview
The `Perspective_ProductQA` module allows customers to ask questions on Product Detail Pages (PDP) and enables administrators to manage questions and provide answers through the Magento 2 Admin Panel.

This module is built following Magento 2 Best Practices, including Service Contracts, Declarative Schema, KnockoutJS components via AJAX, and GraphQL support.

## Features
- **Frontend KnockoutJS Component**: Modern Q&A block on the PDP. Data loaded exclusively via AJAX REST controller.
- **Admin UI Grid & Form**: Manage questions, view answers, change status (Pending/Approved/Rejected), and perform Mass Actions (Approve, Reject, Delete).
- **Service Contracts**: Full Repository architecture for Questions and Answers.
- **GraphQL API**: Query approved product questions and submit new questions via mutation.
- **Access Control List (ACL)**: `view`, `edit`, and `answer` granular permissions in the Admin Panel.
- **Tests**: Covered with PHPUnit (Unit & Integration).

## Installation

### Via Composer
If published to a composer repository:
```bash
composer require perspective/module-product-qa
bin/magento setup:upgrade
bin/magento cache:flush
```

### Manual Installation
Copy the code to `app/code/Perspective/ProductQA`.
```bash
bin/magento module:enable Perspective_ProductQA
bin/magento setup:upgrade
bin/magento cache:flush
```

## GraphQL Examples

### Query Product Questions
```graphql
query GetQuestions {
  productQuestions(productId: 1) {
    items {
      question_id
      author_name
      question_text
      created_at
      answers {
        answer_text
        created_at
      }
    }
  }
}
```

### Create Question Mutation
```graphql
mutation CreateQuestion {
  createProductQuestion(
    input: {
      product_id: 1,
      author_name: "John Doe",
      author_email: "john@example.com",
      question_text: "Does this come with a warranty?"
    }
  ) {
    success
    message
  }
}
```

## Code Quality Check Commands

**Unit Tests**:
```bash
vendor/bin/phpunit -c dev/tests/unit/phpunit.xml.dist app/code/Perspective/ProductQA/Test/Unit/
```

**Integration Tests**:
```bash
vendor/bin/phpunit -c dev/tests/integration/phpunit.xml.dist app/code/Perspective/ProductQA/Test/Integration/
```

**Magento Coding Standard**:
```bash
vendor/bin/phpcs --standard=Magento2 app/code/Perspective/ProductQA/
```
