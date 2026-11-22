---
name: testing-best-practices
description: "Laravel test design and review. Use when selecting coverage, naming or structuring tests, choosing assertions or test data, isolating dependencies, testing HTTP or security boundaries, improving suite performance, or reviewing test value. Use framework guidance or search-docs for Pest and PHPUnit syntax."
license: MIT
metadata:
  author: laravel
---

# Testing best practices

This skill provides rules for designing Laravel tests. Use `search-docs` only when unsure of Laravel or PHPUnit syntax.
This project uses PHPUnit. Follow the corresponding guidance in each rule.

## Consistency first

Read nearby tests before you choose syntax and organization.

A pattern repeated throughout the project is a convention, and project conventions take precedence over this skill. Follow them and give new tests the same structure.

These rules govern the tests you write now. An existing test that follows a project convention is not defective merely because it conflicts with this skill. Do not delete or rewrite it. If the convention has drawbacks, explain them and let the user decide.

## What to test

Read this section before you write a test.

- Test observable behavior and application contracts. A test must pass after an implementation change if the behavior stays the same.
- Cover every changed decision and each applicable high-value failure mode. A decision is a branch, a validation, a calculation, or an authorization.
- Exercise declarations through behavior instead of repeating their text.
- Leave framework behavior to framework tests. Testing project configuration is not testing the framework. A constrained relationship, cast, scope, or validation rule belongs to this project.
- Keep every test that can detect a distinct defect. When two tests detect the same defect, trim the higher-layer test to one case and report the duplication. Do not delete an existing test.
- Write a feature test first. Write a unit test only for logic that does not use the framework.
- Write a feature test for every behavior reachable through a request. Real-browser tests require `laravel/dusk` and a browser download, neither of which this project installs. Mention the package only if the user asks for a real-browser test.
- Use the test tools that the project installs. Add a new test dependency, plugin, or browser only after the user asks for it.

## How to apply

1. Read the code under test and one or two nearby tests. Identify every decision in the code.
2. Read a rule file only when the change needs it and you have not read it this session: usually one, at most two.
3. Report each defect in the code before you write a test. Examples are a method with no body, a policy that no action calls, and a write action with no validation. Test the actual behavior. Report the defect to the user.
4. Write the tests. Run the smallest set of tests that covers the change. The tests must pass.
5. Before finishing, check:
   - Tests cover behaviour, not implementation details or framework behaviour; no two tests catch the same defect.
   - Every changed decision (branch, validation, calculation, authorization) is covered.
   - Files are `{ClassName}Test.php`; test names state the result and the condition.
   - HTTP tests cover login, another user's record (404), and the validation message the user sees.
   - Each test creates its own data and controls time, randomness and outbound HTTP.
   - Write tests assert the response and the database state, against known expected values.
   Open `rules/review.md` only when the user asks for a test review.

## Rule index

Open rule files only as step 2 allows.

| Subject | Rule file |
| --- | --- |
| Test framework features that may already do the work | [`rules/finding-features.md`](rules/finding-features.md) |
| File layout, test names, and groups | [`rules/naming.md`](rules/naming.md) |
| Arrange-act-assert and choosing the correct assertion | [`rules/assertions.md`](rules/assertions.md) |
| Endpoint coverage, authentication, authorization, tenant isolation, validation, and browser tests | [`rules/endpoint-tests.md`](rules/endpoint-tests.md) |
| Factories, test data ownership, and repeated input values | [`rules/test-data.md`](rules/test-data.md) |
| Fakes, mocks, outbound HTTP, time, randomness, and databases | [`rules/isolation.md`](rules/isolation.md) |
| Escaping, injection, cross-tenant access, and privilege checks | [`rules/security.md`](rules/security.md) |
| Environment and CI settings for a slow suite | [`rules/performance.md`](rules/performance.md) |
| Reviewing a test or suite | [`rules/review.md`](rules/review.md) |
