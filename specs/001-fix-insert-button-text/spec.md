# Feature Specification: Fix Insert Button State

**Feature Branch**: `001-fix-insert-button-text`  
**Created**: 2026-02-06  
**Status**: Draft  
**Input**: User description: "fix the issue, after generating and inserting the data successfully button text still remaining 'Generating...' when insert is enabled"

## User Scenarios & Testing *(mandatory)*

### User Story 1 - Insert Completion Resets Button (Priority: P1)

As a user generating data with direct insert enabled, I want the Generate button to return to its normal state after a successful insert so I can run another generation without refreshing.

**Why this priority**: A stuck "Generating..." state blocks the primary workflow after a successful run.

**Independent Test**: Enable insert, run a generation that succeeds, and verify the button label and enabled state reset while the completion message remains visible.

**Acceptance Scenarios**:

1. **Given** insert is enabled and generation completes successfully, **When** the response returns, **Then** the button label is "Generate Data" and the button is enabled.
2. **Given** insert is enabled and generation fails, **When** the error is shown, **Then** the button label is "Generate Data" and the button is enabled.

---

### Edge Cases

- What happens when insert is enabled and the server responds with a completed status but no download URL? The button still resets and the user sees completion messaging.
- How does system handle a network error during insert? The button resets and a clear error message is shown.

## Requirements *(mandatory)*

### Functional Requirements

- **FR-001**: System MUST reset the Generate button label to "Generate Data" after a successful insert-enabled run.
- **FR-002**: System MUST re-enable the Generate button after any insert-enabled run completes, succeeds, or fails.
- **FR-003**: System MUST show a completion message for successful insert-enabled runs without requiring a page refresh.
- **FR-004**: System MUST display a clear error message when insert fails.
- **FR-005**: System MUST not delete or overwrite existing data in target tables during insert.
- **FR-006**: System MUST block insert-enabled runs when the selected format is not SQL and explain why.

## Success Criteria *(mandatory)*

### Measurable Outcomes

- **SC-001**: In 100% of successful insert-enabled runs, the button label resets to "Generate Data" within 1 second of the response.
- **SC-002**: In 100% of failed insert-enabled runs, the button label resets to "Generate Data" within 1 second of error display.
- **SC-003**: Users can start a second insert-enabled generation without reloading the page in under 10 seconds after a successful run.
- **SC-004**: Support reports about a stuck "Generating..." button for insert-enabled runs are reduced to zero in regression testing.

## Assumptions

- Insert-enabled runs can complete synchronously and return a response without queueing.
- Users expect the Generate button to be reusable without a page refresh.

## Dependencies

- The generate endpoint returns a definitive success or failure response for insert-enabled runs.
