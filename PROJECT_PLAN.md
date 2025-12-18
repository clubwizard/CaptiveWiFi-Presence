# CaptiveWiFi Analytics Platform - Project Plan & Status

## Executive Summary

**Project:** Multi-tenant SaaS WiFi analytics platform with FortiGate integration
**Current Phase:** 2 of 5 (40% Complete)
**Status:** ✅ Foundation and backend infrastructure complete
**Next Decision Point:** Proceed with UI/Frontend development or focus on testing/deployment?

---

## Completed Work (Phases 1-2)

### ✅ Phase 1: Core Infrastructure (COMPLETE)
- Laravel 11.x with multi-tenancy (stancl/tenancy)
- PostgreSQL landlord + tenant database architecture
- Redis for caching, sessions, queues
- All Eloquent models with relationships
- Core services: FortiGateApiService, MacAddressHasher, AnalyticsCalculator
- Health check endpoint for monitoring
- Comprehensive .env.example

### ✅ Phase 2: FortiGate Integration (COMPLETE)
- PollFortiGateClients job (60s polling)
- CalculateAnalytics job (hourly/daily snapshots)
- GenerateDailyReport job (2am daily with cleanup)
- Console commands for all jobs
- Laravel scheduler configuration
- Session detection with MAC hashing
- Returning visitor identification
- Multi-device FortiGate support

---

## Remaining Phases (3-5)

### Phase 3: Analytics Engine Enhancements
**Status:** 70% Complete (calculation logic done, needs refinement)
**Estimated Effort:** 1-2 days

#### Tasks
- [ ] Add per-AP analytics breakdown
- [ ] Implement comparison views (week-over-week, month-over-month)
- [ ] Add configurable data retention per tenant
- [ ] Create analytics export service (CSV/JSON)
- [ ] Optimize peak calculation algorithm (currently samples every 5 min)

#### Acceptance Criteria
- Analytics calculate correctly for 10,000+ sessions
- Export generates files <2MB for 90 days of data
- Retention policy runs in <30s per tenant

---

### Phase 4: Dashboard UI (Livewire + Charts)
**Status:** 0% Complete
**Estimated Effort:** 5-7 days
**⚠️ BLOCKER:** Frontend framework choice impacts timeline

#### Tasks
**Real-Time Dashboard**
- [ ] Create RealTimeOverview Livewire component
- [ ] Active sessions list with auto-refresh
- [ ] Current connected clients count (live)
- [ ] Connection/disconnection feed
- [ ] Per-AP client distribution chart

**Analytics Dashboard**
- [ ] Date range selector component
- [ ] Key metrics cards (visitors, dwell time, peak, return rate)
- [ ] ApexCharts integration:
  - Line chart: Footfall trends
  - Bar chart: Hourly distribution
  - Heat map: AP/zone distribution
- [ ] Comparison view (date ranges)

**Access Points Management**
- [ ] AP list table with status indicators
- [ ] AP edit form (location, zone, floor)
- [ ] Per-AP analytics view
- [ ] AP performance metrics

**Settings Page**
- [ ] FortiGate connection configuration UI
- [ ] Data retention policy selector
- [ ] Export data functionality
- [ ] User management (if admin)

#### Acceptance Criteria
- Dashboard loads in <2s with 1000+ sessions
- Charts render smoothly with 90 days of data
- Real-time updates every 30s via Livewire polling
- Mobile responsive (tables collapse appropriately)

#### Dependencies
- TailwindCSS (already configured)
- Alpine.js (already configured)
- ApexCharts (needs installation: `npm install apexcharts`)

---

### Phase 5: Production Ready
**Status:** 0% Complete
**Estimated Effort:** 5-7 days
**⚠️ BLOCKER:** Authentication strategy must be decided

#### Tasks
**Authentication & Authorization**
- [ ] Laravel Breeze installation (or custom auth)
- [ ] Tenant user registration/login
- [ ] Password reset flows
- [ ] Role-based middleware (admin vs viewer)
- [ ] 2FA implementation (optional)

**Landlord Admin Panel**
- [ ] Super admin authentication
- [ ] Tenant CRUD interface
- [ ] Tenant creation wizard (with FortiGate setup)
- [ ] Global stats dashboard
- [ ] Tenant impersonation for support

**API Endpoints (for CaptiveWiFi.com integration)**
- [ ] API token generation/management
- [ ] RESTful endpoints:
  - GET /api/v1/analytics/current
  - GET /api/v1/analytics/daily
  - GET /api/v1/analytics/range
  - GET /api/v1/clients/active
  - GET /api/v1/access-points
- [ ] API rate limiting (60 req/min per tenant)
- [ ] API documentation (Swagger/OpenAPI)

**Testing Suite**
- [ ] Unit tests for services (MacHasher, Analytics)
- [ ] Feature tests for tenant isolation
- [ ] Integration tests for FortiGate API (mocked)
- [ ] Job tests (polling, analytics, reports)
- [ ] Browser tests for dashboard (Dusk)
- [ ] Target: 80%+ code coverage

**DigitalOcean Deployment**
- [ ] Create App Platform spec YAML
- [ ] Environment variable documentation
- [ ] Database migration strategy
- [ ] Queue worker configuration
- [ ] Scheduler setup (artisan schedule:work)
- [ ] Log management strategy
- [ ] Backup strategy for tenant databases

**Documentation**
- [ ] API documentation (endpoints, authentication)
- [ ] Deployment guide (DigitalOcean)
- [ ] Admin user guide (tenant management)
- [ ] Venue staff user guide (dashboard usage)
- [ ] Developer documentation (architecture)
- [ ] Troubleshooting guide

#### Acceptance Criteria
- All tests pass with 80%+ coverage
- App deploys to DigitalOcean without manual intervention
- Zero data leakage between tenants (verified by tests)
- API endpoints documented and functional
- Complete admin and user guides available

---

## Quality Assurance Strategy

### Testing Pyramid
```
                    /\
                   /  \
                  / E2E \        Browser Tests (10%)
                 /______\
                /        \
               / Integration\   API/Job Tests (30%)
              /____________\
             /              \
            /   Unit Tests   \  Service/Model Tests (60%)
           /________________\
```

### Test Coverage Targets
- **Unit Tests:** 90%+ coverage for services/models
- **Feature Tests:** 80%+ coverage for controllers/jobs
- **Integration Tests:** All critical paths covered
- **Browser Tests:** Happy path + critical user flows

### Manual QA Checklist (Pre-Production)
- [ ] Create test tenant via landlord panel
- [ ] Configure FortiGate credentials (use mock API)
- [ ] Verify polling job creates sessions
- [ ] Check analytics calculations are accurate
- [ ] Test dashboard loads and displays data correctly
- [ ] Verify tenant isolation (no cross-tenant data visible)
- [ ] Test data retention cleanup
- [ ] Verify API endpoints return correct data
- [ ] Test authentication flows (login, logout, password reset)
- [ ] Test role-based access control

---

## Identified Blockers & Risks

### 🔴 Critical Blockers
1. **No Real FortiGate Device for Testing**
   - **Impact:** Cannot verify actual API responses
   - **Mitigation:** Create mock FortiGate API server for testing
   - **Resolution Time:** 4-6 hours

2. **PostgreSQL Not Running Locally**
   - **Impact:** Cannot run migrations or test database operations
   - **Mitigation:** Use Docker PostgreSQL container or SQLite for dev
   - **Resolution Time:** 1 hour

3. **Redis Not Running Locally**
   - **Impact:** Cannot test queues, caching, sessions
   - **Mitigation:** Use Docker Redis container
   - **Resolution Time:** 30 minutes

### 🟡 Medium Risks
1. **DigitalOcean Tenant Database Limits**
   - **Risk:** Creating 100+ tenant databases may hit DO limits
   - **Mitigation:** Research DO PostgreSQL limits, consider schema-based isolation
   - **Impact:** Could require architecture change

2. **FortiGate API Rate Limits Unknown**
   - **Risk:** Polling every 60s may trigger rate limits
   - **Mitigation:** Add exponential backoff, configurable poll intervals
   - **Impact:** May need polling frequency adjustment

3. **ApexCharts Performance with Large Datasets**
   - **Risk:** Charts may be slow with 90 days of hourly data
   - **Mitigation:** Implement data aggregation, lazy loading
   - **Impact:** May require chart optimization

### 🟢 Low Risks
1. **Browser Compatibility** - Mitigated by using standard frameworks
2. **Timezone Handling** - Laravel handles this well
3. **Data Migration** - No existing data to migrate

---

## Milestones & Timeline

### Milestone 1: Backend Complete ✅ (ACHIEVED)
- **Date:** Current
- **Deliverables:** Phases 1-2 complete, all backend logic functional
- **Status:** COMPLETE

### Milestone 2: Dashboard MVP 🎯 (RECOMMENDED NEXT)
- **Target:** +5-7 days from approval
- **Deliverables:**
  - Working Livewire dashboard
  - Real-time client tracking visible
  - Basic charts (line, bar)
  - AP management interface
- **Success Criteria:** Can view live WiFi analytics in browser

### Milestone 3: Authentication & Multi-User
- **Target:** +3-4 days from Milestone 2
- **Deliverables:**
  - User login/registration
  - Role-based access
  - Landlord admin panel
- **Success Criteria:** Multiple users can access tenant dashboard

### Milestone 4: API & Integration Ready
- **Target:** +2-3 days from Milestone 3
- **Deliverables:**
  - REST API endpoints
  - API documentation
  - Integration guide for CaptiveWiFi.com
- **Success Criteria:** External system can fetch analytics via API

### Milestone 5: Production Deployment 🚀
- **Target:** +3-4 days from Milestone 4
- **Deliverables:**
  - Complete test suite (80%+ coverage)
  - DigitalOcean deployment
  - Full documentation
  - Admin/user guides
- **Success Criteria:** App running in production, accepting real traffic

**Total Estimated Timeline:** 18-23 days from current point

---

## Documentation Status

### Completed ✅
- [x] README.md with setup instructions
- [x] Database schema documentation
- [x] Service usage examples (in README)
- [x] .env.example with all variables

### In Progress 🚧
- [ ] API documentation (Phase 5)
- [ ] Deployment guide (Phase 5)

### Not Started ❌
- [ ] Admin user guide
- [ ] Venue staff user guide
- [ ] Developer architecture guide
- [ ] Troubleshooting guide

---

## Current Architecture Assessment

### ✅ Strengths
- **Scalability:** Multi-tenant with database isolation scales well
- **Privacy:** MAC hashing ensures GDPR compliance
- **Reliability:** Queue-based jobs with retry logic
- **Monitoring:** Health check endpoint and comprehensive logging
- **Maintainability:** PSR-12 standards, type hints, clear separation of concerns

### ⚠️ Areas for Improvement
1. **Testing:** No tests written yet (critical for production)
2. **Error Recovery:** Need dead letter queue for failed jobs
3. **Monitoring:** Need application performance monitoring (APM)
4. **Caching:** Analytics snapshots could be cached more aggressively
5. **Security:** No rate limiting on health check endpoint

---

## Decision Point: Recommended Path Forward

### Option A: Continue to MVP (RECOMMENDED)
**Focus:** Build minimal dashboard to visualize what we've built

**Pros:**
- Can see the system working end-to-end
- Validates backend functionality visually
- Provides demo-able product
- Identifies integration issues early

**Cons:**
- Delays testing phase
- Frontend work without tests is riskier

**Timeline:** 5-7 days to working dashboard

---

### Option B: Testing First Approach
**Focus:** Write comprehensive test suite before UI

**Pros:**
- Validates all backend logic works correctly
- Catches bugs before UI development
- Test-driven UI development is safer
- Better code quality

**Cons:**
- No visual progress to show
- Harder to test without UI interactions
- May need to refactor tests after UI changes

**Timeline:** 4-6 days to full test suite

---

### Option C: Production-Ready Backend
**Focus:** Authentication, API, deployment infrastructure

**Pros:**
- Backend is fully productionized
- Can integrate with external systems immediately
- Security in place early

**Cons:**
- No UI to demonstrate functionality
- Harder to validate without visual feedback
- May build API that UI doesn't need

**Timeline:** 5-7 days to production backend

---

### Option D: Parallel Development
**Focus:** Build UI while writing tests concurrently

**Pros:**
- Fastest to completion
- Tests and UI progress together
- Can catch issues in both layers

**Cons:**
- More complex to manage
- Potential rework if issues found
- Requires careful coordination

**Timeline:** 8-10 days to completion (both done)

---

## My Recommendation: Option A + Testing

**Phase 4 First (Dashboard UI)** - 5-7 days
1. Build Livewire dashboard components
2. Integrate ApexCharts
3. Create basic auth (simple login, no registration yet)
4. Wire up to real data

**Then Phase 5 (Production Polish)** - 5-7 days
5. Write comprehensive test suite (with UI to test against)
6. Add API endpoints
7. Create deployment configuration
8. Write documentation

### Why This Order?
1. **Visual Validation:** See if polling and analytics actually work
2. **Easier Debugging:** UI makes it obvious if data is wrong
3. **Stakeholder Demo:** Can show working product earlier
4. **Informed Testing:** Writing tests with working UI is easier
5. **Motivation:** Seeing progress keeps momentum

---

## Resources Required

### Development Environment Setup
```bash
# Required services (Docker Compose recommended)
- PostgreSQL 15+
- Redis 7+

# Or quick start with SQLite + Array cache (dev only)
- Just needs PHP 8.2+ and Composer
```

### External Dependencies
- FortiGate device OR mock API server (for testing)
- DigitalOcean account (for deployment)
- NPM/Node.js (for frontend assets)

### Time Commitment
- **Option A (Recommended):** 10-14 days total
- **Option B (Testing First):** 12-16 days total
- **Option C (Backend Only):** 8-10 days total
- **Option D (Parallel):** 10-12 days total (but more intense)

---

## Approval Decision Required

### Questions for You:

1. **Which approach do you want to take?** (A, B, C, or D)

2. **Do you have access to:**
   - PostgreSQL database? (or use SQLite for dev?)
   - Redis server? (or use file/array cache for dev?)
   - Real FortiGate device? (or should I build a mock API?)

3. **Authentication preference:**
   - Laravel Breeze (official, simple)
   - Laravel Jetstream (feature-rich with teams)
   - Custom auth (minimal, specific to needs)

4. **Priority features for Milestone 2 (Dashboard MVP):**
   - Real-time monitoring (live client count)
   - Historical analytics (charts, trends)
   - Access point management
   - All of the above (full Phase 4)

5. **Testing coverage target:**
   - 60% (basic)
   - 80% (production-ready)
   - 90%+ (mission-critical)

6. **Deployment timeline:**
   - ASAP (skip some testing, deploy with MVP)
   - After testing (recommended, ~2 weeks)
   - After everything perfect (3+ weeks)

---

## Next Steps After Approval

Once you approve the direction, I will:

1. ✅ Update this plan based on your choices
2. ✅ Create detailed task breakdown for chosen phase
3. ✅ Set up any required dev environment (Docker, mocks)
4. ✅ Begin implementation with regular checkpoints
5. ✅ Provide progress updates at each milestone

**Ready to proceed?** Let me know which option you prefer and answers to the questions above!
