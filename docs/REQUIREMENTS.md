# Requirement traceability

This matrix maps the implementation's principal systems to verification. A check mark in a release report means its named suite actually ran; source presence alone is not reported as verification.

| Area | Implementation | Verification |
| --- | --- | --- |
| Classic/block domain | `Domain`, `Adapters`, `NativeRepository` | PHP unit + WP integration + block fixtures |
| Tree editing/invariants | `src/domain/tree.ts`, reducer, Tree UI | Vitest + component + keyboard E2E |
| Draft/publish/conflict/revisions | repositories and REST façade | WP integration + conflict E2E |
| Content discovery | `ContentSearch`, Content Library | integration + request-count audit |
| Inspector/responsive/conditions/mega metadata | Inspector, ConditionEngine, Renderer | component + frontend E2E + axe |
| Import/export/templates | TransferService and repositories | unit + fuzz + rollback E2E |
| Accessibility/RTL | tree semantics, Move dialog, logical CSS, live regions | axe + keyboard + manual AT review |
| Security/privacy | capabilities, schemas, sanitizers, opt-ins | adversarial tests + Plugin Check |
| Performance | flat normalized state, targeted search, conditional assets | 100/500/1,000 node benchmark |
| Release | deterministic ZIP tool and wp-env | clean ZIP install/upgrade matrix |

The detailed source prompt contains conditional integration and environment-dependent manual checks. Test reports must distinguish implemented code from executed coverage and must never claim unavailable browser, screen-reader, WooCommerce, multilingual, theme, or version coverage.
