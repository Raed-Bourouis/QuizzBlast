# Game Session Implementation Guide

## Overview
This document describes the complete game session implementation for QuizzBlast, including all features, routes, and usage instructions.

## Features Implemented

### 1. Entity Updates
- **GameSession**: Added `endedAt` property to track when games finish
- **GameParticipant**: Added `isHost` flag and `addScore()` method
- **PlayerAnswer**: Added `isCorrect` property to track answer correctness

### 2. New Routes

#### Frontend Routes (GameFrontendController)
- `GET /game/join` - Page to enter game code and join
- `GET /game/{code}/lobby` - Waiting room before game starts
- `GET /game/{code}/play` - Main game interface
- `GET /game/{code}/leaderboard` - Final results and rankings

#### API Routes (GameController)
- `POST /api/game/session/create/{quizId}` - Host creates new game session
- `POST /api/game/session/join/{code}` - Player joins existing session
- `POST /api/game/session/{id}/start` - Start the game
- `POST /api/game/session/{id}/next` - Move to next question
- `POST /api/game/session/{id}/end` - End the game
- `POST /api/game/answer/submit` - Submit player answer
- `GET /api/game/session/{id}/state` - Get current game state

### 3. Game Flow

#### For Host:
1. Navigate to quiz details page
2. Click "Start Game" button
3. System generates 6-character game code
4. Share code with players
5. Monitor lobby as players join
6. Click "Start Game" when ready
7. Control question progression with "Next Question"
8. Click "End Game" to finish
9. View final leaderboard

#### For Players:
1. Navigate to `/game/join`
2. Enter 6-digit game code
3. Wait in lobby for host to start
4. Answer questions with timer
5. See score updates in real-time
6. View final leaderboard at end

### 4. Real-Time Updates

All real-time features use AJAX polling (no WebSocket required):
- **Lobby**: Polls every 1 second for new players and game start
- **Play**: Polls every 1 second for question changes
- **Host**: Polls every 2 seconds for participant updates

### 5. Scoring System

Base score: Points defined in question
Time bonus: Up to 20% additional points for fast answers

Formula:
```
if correct:
    points = question.points
    if answered_time < time_limit:
        time_bonus = (1 - (answered_time / time_limit)) * 0.2
        points = points * (1 + time_bonus)
```

Example: 100 point question answered in 5 seconds with 30 second limit:
- Base: 100 points
- Time bonus: (1 - 5/30) * 0.2 = 0.167 (16.7%)
- Final: 100 * 1.167 = 117 points

### 6. Validation & Security

#### Answer Submission Validation:
- Session must be IN_PROGRESS
- Question must be current question
- No duplicate answers allowed
- Answer must belong to question
- Participant must be in session

#### Host Authorization:
- Only session host can start/end game
- Only session host can navigate questions

### 7. Database Migration

Run this migration to add new columns:
```bash
php bin/console doctrine:migrations:migrate
```

Adds:
- `game_session.ended_at` (DATETIME, nullable)
- `game_participant.is_host` (BOOLEAN, default false)
- `player_answer.is_correct` (BOOLEAN, not null)

## Testing Checklist

### Basic Flow
- [ ] Host can create game session from quiz
- [ ] Players can join using game code
- [ ] Lobby shows all participants
- [ ] Host can start game
- [ ] Players see questions with timer
- [ ] Players can submit answers
- [ ] Scores update correctly
- [ ] Time bonus calculated properly
- [ ] Host can navigate questions
- [ ] Game ends properly
- [ ] Leaderboard displays correctly

### Validation Tests
- [ ] Cannot join with invalid code
- [ ] Cannot join finished game
- [ ] Cannot submit duplicate answer
- [ ] Cannot answer wrong question
- [ ] Cannot answer after time expires
- [ ] Non-host cannot control game

### Edge Cases
- [ ] Player leaves during game
- [ ] Multiple players answer simultaneously
- [ ] Network timeout during answer submission
- [ ] Game ends with no answers
- [ ] Single player game

## UI Templates

### Join Page (`templates/game/join.html.twig`)
- 6-digit code input
- Auto-uppercase formatting
- Validation feedback
- Network error handling

### Lobby (`templates/game/lobby.html.twig`)
- Game code display
- Real-time participant list
- Join time for each player
- Host indicator
- Leave game button

### Play (`templates/game/play.html.twig`)
- Score display
- Question progress (X of Y)
- Countdown timer
- Question text
- Answer buttons
- Feedback after answering
- Correct/incorrect indicators

### Leaderboard (`templates/game/leaderboard.html.twig`)
- Winner announcement
- Top 3 podium display
- Full rankings list
- Score display
- Play again button (for host)

### Host View (`templates/game_master/host.html.twig`)
- Game code display
- Current question display
- Answer progress (X/Y answered)
- Participant list with scores
- Control buttons:
  - Start Game
  - Next Question
  - End Game

## Architecture Notes

### Why AJAX Polling Instead of WebSocket?
- Simpler infrastructure (no WebSocket server needed)
- More reliable across different network conditions
- Easier to debug and maintain
- Lower latency requirements for quiz game
- Better compatibility with various hosting environments

### Route Organization
- **GameFrontendController**: Player-facing pages (GET)
- **GameController**: API endpoints (POST/GET)
- **GameMasterController**: Host session creation (GET)

### Security Considerations
- All answer submissions validated server-side
- Session status checked before operations
- Host authorization verified for control actions
- No client-side score manipulation possible
- XSS prevention with escapeHtml() functions

## Troubleshooting

### Players can't join
- Verify code is correct (6 characters, alphanumeric)
- Check session status is WAITING
- Ensure user is authenticated

### Real-time updates not working
- Check browser console for network errors
- Verify API routes are accessible
- Check polling intervals haven't been stopped

### Scores not updating
- Verify answer validation passes
- Check question belongs to quiz
- Ensure answer isCorrect property is set
- Verify participant addScore() is called

### Game won't start
- Ensure at least one participant joined
- Verify user is the host
- Check session status is WAITING

## Future Enhancements

Potential improvements:
1. Server-Sent Events (SSE) for real-time updates
2. Answer statistics display for host
3. Question media support (images, videos)
4. Power-ups and multipliers
5. Team-based gameplay
6. Custom time limits per question
7. Answer explanations after each question
8. Session replay feature
9. Export results to CSV/PDF
10. Spectator mode for non-players

## Support

For issues or questions:
1. Check this documentation
2. Review code comments in controllers
3. Check browser console for errors
4. Verify database migrations ran successfully
5. Test with simplified quiz (2-3 questions)
