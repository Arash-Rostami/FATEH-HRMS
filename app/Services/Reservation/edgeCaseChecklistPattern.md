Role: Advanced Static Analysis Engine & In-Memory PHP Runtime Simulator.
Objective: Conduct a ruthless, microscopic code review and virtual dry-run of a highly customized Reservation & Resource Allocation System written in Laravel, Livewire, and Filament PHP. Your singular objective is to break the system logic, surface hidden bugs, expose unhandled exceptions, and identify data-leak gaps.

Execution Strategy:
1. Virtual State Tracking: Maintain a virtual in-memory state of the database tables (Users, Resources, Reservations, ReservationPolicies) and request payloads for each scenario.
2. Step-by-Step Simulation: Dry-run the code by evaluating the interaction between layers (e.g., how a state set in a Livewire frontend interacts with the custom Validator rules and the underlying Eloquent model lifecycle hooks).
3. Evaluate every query boundary, conditional branch, array iteration, and early return statement.

Output Format Requirement:
For each test scenario listed below, evaluate the logic and provide a brief status verdict formatted exactly like this:
- [PASSED] If the provided implementation logic handles it completely and safely.
- [FAILED / GAP FOUND] If a specific edge-case scenario exposes an unhandled exception, data mismatch, or structural leak based on how the methods interact. State exactly which lines or architectural flows cause the breakdown, and provide the most optimally minimal, clean, comment-free code solution to resolve it.

---

### REFERENCE LIST OF TEST CONDITIONS

#### 1. USER & RESOURCE STATE INTEGRITY
- [ ] Inactive users must be blocked from initiating or completing a booking sequence.
- [ ] Users lacking explicit booking permissions for a specific resource type must fail immediately.
- [ ] Input Format Flexibility: Confirm the validator safely interprets both flat JSON structures and advanced array-of-objects booking payloads without casting exceptions.
- [ ] Empty Permission Safeguard: If a resource type permission column is empty or missing, it must default to denying all actions.
- [ ] Inactive resources must be completely blocked from receiving bookings across all actions (Admin & User).

#### 2. CHRONOLOGICAL & DATE INTEGRITY (CARBON/BOUNDARIES)
- [ ] Bookings where the execution time falls in the past must be rigidly blocked.
- [ ] Inverted Timelines: Scrutinize every path to ensure an end_time before or exactly equal to a start_time is caught before any resource processing or allocation takes place.
- [ ] Full-Day Exclusions: Verify that full-day reservations safely bypass hour-based duration limits and specific operational window validations without leaving scheduling gaps.
- [ ] Timezone / Midnight Shifts: Check if calculations crossing calendar day boundaries or executing exactly at date rollover (00:00:00) leak availability or corrupt durations.

#### 3. POLICY & WINDOW BOUNDARY RULES
- [ ] Ceiling Breach: Bookings exceeding the specified window_days limit must fail.
- [ ] Feature Flags: A null value for window_days must behave as an unrestricted future pass, whereas a value of exactly 0 must block all future allocations.
- [ ] Minimum Notice Boundaries: Confirm that window_hours constraints are accurately preserved across multi-day transitions (e.g., requiring 12 hours notice for a tomorrow morning slot).
- [ ] Policy Value Sanitation: Ensure falsey configuration values (e.g., boolean false, integer 0) are evaluated strictly and not stripped out or auto-cast to null.

#### 4. OPERATIONAL CALENDAR & HOURS
- [ ] Operational Limits: Any booking request falling partially or entirely outside allowed operational blocks must fail.
- [ ] Edge Boundary Alignment: A slot finishing precisely on the closing minute must pass, while finishing one minute late must be rejected.
- [ ] Overnight Continuity: Inspect the logic for cross-midnight schedules (e.g., allowed hours 22:00 to 06:00). Ensure non-contiguous daytime frames (like 10:00 to 23:00) cannot cheat the evaluation window via chronological shifts.

#### 5. DURATIONS & MEASUREMENTS
- [ ] Duration Ceilings/Floors: Validate that reservations shorter than min_duration_minutes or longer than max_duration_minutes are blocked.
- [ ] Chronological Primacy: Confirm that the chronological timeline check (start >= end) evaluates before any duration difference calculation is executed, preventing negative integer overlaps.

#### 6. DATA AVAILABILITY & MATRICES
- [ ] Double-Booking: Multiple users or processes must never be allowed to allocate the same resource for overlapping time brackets.
- [ ] Multi-Day Spread: Confirm that multi-day spans properly flag conflicts across all intersecting dates, not just the start and end anchors.
- [ ] Null Suppression: In full-day setups where timestamps might be recorded as NULL, the query layer must firmly trap conflicts using structural date alternatives (like created_at matching or boolean locks).
- [ ] Released Records Behavior: If allow_overlap_release is configured to false, any canceled/released reservation must continue to block that time block. Ensure the UI and backend validation align perfectly on this fallback rule.
- [ ] Self-Exclusion: During editing updates, ensure the record's own ID is successfully passed to the exclusion matrix to prevent a reservation from conflicting with itself.

#### 7. QUOTAS & USER CONFLICTS
- [ ] User Concurrency: A user must be blocked from reserving different resources within overlapping windows if the policies forbid concurrent user activity.
- [ ] Active Quotas: Check user limits (e.g., maximum concurrent active reservations). Ensure the query accurately scopes the count by resource type rather than applying it globally.
- [ ] Released Quotas Release: Determine if early-released reservations continue to count against the user's quota, and confirm this logic matches business rules.

#### 8. CANCELLATION & SERIES LIFECYCLE
- [ ] Repeat Bookings: Recurring series creation must fail outright if allow_repeat is toggled off.
- [ ] Atomic Block Isolation: During a series generation loop, each occurrence must be validated individually. Conflicting segments must be skipped safely without crashing the transaction or creating overlapping blocks.
- [ ] Double Cancellation: Attempting to cancel a record that is already marked as inactive or canceled must return a clean domain validation exception.
- [ ] Ownership Controls: Non-admin users must be strictly blocked from altering or canceling reservations owned by other identifiers.
- [ ] Cascading Actions: When a master series record is dropped, ensure all active children cascade downward into a canceled state, while already canceled children are preserved without repetitive updates.

#### 9. COMPONENT & ARCHITECTURAL VULNERABILITIES
- [ ] Data Corruption Loops: Search for self-referential relationships (e.g., a row setting its own ID as its parent_id). Ensure this is blocked at both the Eloquent model saving hooks and runtime actions.
- [ ] External Sync Leaks: When reservations change (such as meeting room blocks), ensure secondary entities like linked calendar events or user alerts are updated or purged symmetrically.
- [ ] Livewire State Drift: Inspect Livewire computed properties (#[Computed]). Ensure that modifying properties like date, start time, or floor dynamically flushes the cache and re-renders the underlying database counts instantly without lingering UI artifacts.
- [ ] Filament Lifecycles: Verify that Filament form states ($this->form->getState()) are correctly transformed before being processed by the validation layers in both Create and Edit controllers.
- [ ] Code Cleanliness: Any optimization snippets provided in the response must be highly optimized, elegant, and completely stripped of comments.
- [ ] Pipeline Context Separation: Verify that the pipeline engine rigidly forces the complete 14-rule suite when `$enforceAllRules` is true (fresh bookings), while successfully evaluating the role-based `skip_admin` exemptions only when the parameter is false (admin edits).
