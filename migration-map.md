Migration map: SQL -> Doctrine entities and route mapping

Tables mapped to Entities:
- `users` -> `App\Entity\User`
- `user_favorites` -> `App\Entity\UserFavorite`
- `languages` -> `App\Entity\Language`
- `verdict_status` -> `App\Entity\VerdictStatus`
- `problems` -> `App\Entity\Problem`
- `test_cases` -> `App\Entity\TestCase`
- `submissions` -> `App\Entity\Submission`
- `contests`, `contest_problems`, `contest_participations`, `contest_results` -> optional, migrate later

Recommended route mapping (modern RESTful):
- GET `/` -> `HomeController::index`
- GET `/problems` -> `ProblemController::index`
- GET `/problems/{id}` -> `ProblemController::show`
- POST `/problems/{id}/submit` -> `SubmissionController::create`
- GET `/submissions` -> `SubmissionController::index`
- GET `/users/{username}` -> `ProfileController::show`
- GET `/leaderboard` -> `LeaderboardController::index`
- Auth: `/login`, `/logout`, `/register` using Symfony Security

Notes:
- Keep templates visually identical: copy `public/assets` CSS/JS into `public/` of Symfony project and use Twig templates that reuse the same markup.
- Worker kept outside Symfony; place under `worker/` and call it separately.
