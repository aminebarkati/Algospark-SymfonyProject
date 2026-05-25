# Worker Judge Guide

This document explains how the judge worker works from boot to verdict write-back and lists every helper method in the script.

## What the worker is

The worker is a standalone CLI script at [worker/worker.php](../worker/worker.php). It is not a Symfony command. It boots the Symfony kernel, uses Doctrine repositories directly, polls for unjudged submissions, and writes verdicts back into the database.

## High level flow

1. Load Composer autoloading and .env values.
2. Refuse to run unless the script is executed from the CLI.
3. Boot the Symfony kernel.
4. Fetch the Doctrine EntityManager and the repositories it needs.
5. Create storage/submission_files if it does not exist.
6. Enter an infinite polling loop.
7. Find the oldest submission with no verdict.
8. Re-open that submission inside a transaction and judge it.
9. Flush the final verdict, execution time, test counts, and judged timestamp.
10. Update the problem statistics.
11. Log the result and repeat.

## Submission selection

The worker selects the next job with these rules:

- Only submissions with a null verdict are eligible.
- The oldest submission by submittedAt is chosen first.
- Ties are broken by id.
- If no submission is available, the worker sleeps for 3 seconds and polls again.

## Source file handling

The worker looks for the source file in storage/submission_files/{userId}/{submissionId}.{ext}.

- For non-Java languages, the file is copied into a temporary work directory as Main plus the language extension.
- For Java, the worker reads the source, detects the package declaration and the public class name, writes the file into the matching directory tree, and returns the fully qualified class name for execution.
- If the source file is missing, the worker treats the submission as a runtime failure path.

## Judging rules

The verdict order is:

- No tests found: RE
- Compile step fails: CE
- Test execution times out: TLE
- Process exits non-zero: RE
- Normalized output differs: WA
- All tests pass: AC

Timing is measured per compile and per run using microtime, and the final execution time stored on the submission is the slowest test run time.

## Output comparison

The worker normalizes both expected and actual output before comparing them.

- Windows line endings are converted to Unix line endings.
- Trailing whitespace is trimmed from each line.
- Trailing blank lines are removed.

This keeps the judge strict on content while being tolerant of formatting noise that should not affect a correct answer.

## Java-specific behavior

Java is handled specially because the source filename must match the public class name.

- The worker detects the package declaration if one exists.
- The worker detects the public class name, with a fallback to a non-public class pattern, then Main if nothing is detected.
- The source is written to the matching path inside the temp directory.
- Compilation uses javac.
- Execution uses java -cp with the detected fully qualified class name.

This is the fix that prevents public class name mismatches from causing avoidable compilation errors when the uploaded file name is different from the class name.

## Temporary files and cleanup

Every submission is judged inside a unique temp directory.

- The source is copied there.
- Input and output files are created there.
- The directory is deleted when judging finishes, whether the submission passes or fails.

## Worker methods

| Method | Purpose |
| --- | --- |
| judgeSubmission | Main judging workflow for one submission: load source, load tests, compile if needed, run tests, compare output, and return a verdict payload. |
| loadVerdicts | Loads the AC, WA, TLE, MLE, RE, CE, and PENDING verdict rows from the database. |
| needsCompile | Decides whether the selected language must be compiled before execution. |
| compileLanguage | Compiles C, C++, or Java source and records compile time and exit status. |
| runCode | Executes the compiled or interpreted solution under timeout with redirected stdin/stdout/stderr. |
| normalizeOutput | Normalizes output before comparing expected and actual text. |
| prepareJavaSourceFile | Detects Java package and class name, writes the source into the correct path, and returns the fully qualified class name. |
| detectJavaPackageName | Extracts a Java package declaration with a regex. |
| detectJavaMainClassName | Extracts the Java class name with regex fallbacks. |
| cleanUp | Deletes the temporary work directory and its contents. |
| log_msg | Prints a timestamped worker log line. |

## Operational notes

- The worker uses the same Doctrine entities and repositories as the web app.
- It updates Submission, Problem, VerdictStatus, and TestCase data indirectly through repository lookups and entity flushing.
- It currently loops forever because MAX_ITERATIONS is 0.
- It is safe to run in a second terminal while the web app is running.
