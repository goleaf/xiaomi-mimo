import assert from 'node:assert/strict';
import { readFileSync } from 'node:fs';
import test from 'node:test';

function source(relativePath: string): string {
    return readFileSync(new URL(relativePath, import.meta.url), 'utf8');
}

test('workspace management show page exposes the four approved management sections', () => {
    const showPage = source('./Show.vue');

    assert.match(showPage, /WorkspacePageHeader/);
    assert.match(showPage, /WorkspaceSegmentedControl/);
    assert.match(showPage, /WorkspaceOverviewPanel/);
    assert.match(showPage, /WorkspaceMembersPanel/);
    assert.match(showPage, /WorkspaceConfigurationPanel/);
    assert.match(showPage, /WorkspaceDangerPanel/);

    for (const section of ['overview', 'members', 'configuration', 'danger']) {
        assert.match(
            showPage,
            new RegExp(`workspaces\\.management\\.navigation\\.${section}`),
        );
    }
});

test('workspace member management covers the complete invitation and role lifecycle', () => {
    const membersPanel = source(
        '../../components/workspace/WorkspaceMembersPanel.vue',
    );

    assert.match(membersPanel, /useHttp/);
    assert.match(membersPanel, /searchQuery/);
    assert.match(membersPanel, /inviteMember/);
    assert.match(membersPanel, /inviteForm\.email = ''/);
    assert.match(membersPanel, /if \(!inviteForm\.wasSuccessful\)/);
    assert.match(membersPanel, /resendInvitation/);
    assert.match(membersPanel, /if \(!resendRequest\.wasSuccessful\)/);
    assert.match(membersPanel, /cancelInvitation/);
    assert.match(membersPanel, /if \(!cancelRequest\.wasSuccessful\)/);
    assert.match(membersPanel, /updateMemberRole/);
    assert.match(membersPanel, /if \(!roleRequest\.wasSuccessful\)/);
    assert.match(membersPanel, /removeMember/);
    assert.match(membersPanel, /if \(!removeRequest\.wasSuccessful\)/);
    assert.match(membersPanel, /WorkspaceConfirmDialog/);
    assert.match(membersPanel, /workspaces\.management\.invitations/);
});

test('workspace ownership and deletion require explicit typed confirmations', () => {
    const dangerPanel = source(
        '../../components/workspace/WorkspaceDangerPanel.vue',
    );

    assert.match(dangerPanel, /transferOwnership/);
    assert.match(dangerPanel, /if \(!transferForm\.wasSuccessful\)/);
    assert.match(dangerPanel, /deleteWorkspace/);
    assert.match(dangerPanel, /if \(!deleteRequest\.wasSuccessful\)/);
    assert.match(dangerPanel, /WorkspaceConfirmDialog/);
    assert.match(dangerPanel, /:confirmation-text="workspace\.name"/);
});

test('workspace portfolio and settings navigation use the canonical management page', () => {
    const portfolio = source('./Index.vue');
    const settingsLayout = source('../../layouts/settings/Layout.vue');

    assert.match(portfolio, /showWorkspace\(workspace\)/);
    assert.match(settingsLayout, /settings\.navigation\.workspace_management/);
    assert.match(settingsLayout, /showWorkspace/);
    assert.doesNotMatch(settingsLayout, /settings\.navigation\.members/);
});
