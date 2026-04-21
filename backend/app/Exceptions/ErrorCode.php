<?php

namespace App\Exceptions;

enum ErrorCode: string
{
    // Auth
    case AuthInvalidCredentials     = 'auth.invalid_credentials';
    case AuthTokenExpired           = 'auth.token_expired';
    case AuthTokenInvalid           = 'auth.token_invalid';
    case AuthRefreshTokenInvalid    = 'auth.refresh_token_invalid';
    case AuthUnauthorized           = 'auth.unauthorized';

    // Enterprise
    case EnterpriseNotFound          = 'enterprise.not_found';
    case EnterpriseForbidden         = 'enterprise.forbidden';
    case EnterpriseHeaderRequired    = 'enterprise.header_required';
    case EnterpriseNotMember         = 'enterprise.not_member';
    case EnterpriseInvitationExpired = 'enterprise.invitation_expired';
    case EnterpriseInvitationInvalid = 'enterprise.invitation_invalid';
    case EnterpriseMemberExists      = 'enterprise.member_already_exists';
    case EnterpriseRoleBaseImmutable   = 'enterprise.role_base_immutable';
    case EnterpriseRoleNotFound        = 'enterprise.role_not_found';
    case EnterpriseRoleHasMembers      = 'enterprise.role_has_members';
    case EnterpriseRoleAssignNotAllowed = 'enterprise.role_assign_not_allowed';

    // Subscription / Plan limits
    case PlanLimitExceeded   = 'subscription.limit_exceeded';
    case SubscriptionInactive = 'subscription.inactive';

    // Workspace
    case WorkspaceNotFound          = 'workspace.not_found';
    case WorkspaceForbidden         = 'workspace.forbidden';
    case WorkspaceDepthExceeded     = 'workspace.depth_exceeded';
    case WorkspaceOrphaned          = 'workspace.orphaned';
    case WorkspaceRoleBaseImmutable = 'workspace.role_base_immutable';
    case WorkspaceRoleHasMembers   = 'workspace.role_has_members';

    // Assets
    case AssetNotFound             = 'asset.not_found';
    case AssetForbidden            = 'asset.forbidden';
    case AssetStorageLimitExceeded = 'asset.storage_limit_exceeded';
    case AssetUploadExpired        = 'asset.upload_expired';

    // Rooms
    case RoomNotFound            = 'room.not_found';
    case RoomForbidden           = 'room.forbidden';
    case RoomInvalidStatus       = 'room.invalid_status';
    case RoomAccessTokenInvalid  = 'room.access_token_invalid';
    case RoomParticipantNotFound = 'room.participant_not_found';

    // Conversations
    case ConversationNotFound  = 'conversation.not_found';
    case ConversationForbidden = 'conversation.forbidden';

    // Messages
    case MessageNotFound  = 'message.not_found';
    case MessageForbidden = 'message.forbidden';

    // Streams
    case StreamNotFound      = 'stream.not_found';
    case StreamForbidden     = 'stream.forbidden';
    case StreamInvalidStatus = 'stream.invalid_status';
    case StreamAlreadyLive   = 'stream.already_live';

    // General
    case ValidationFailed = 'validation.failed';
    case NotFound         = 'general.not_found';
    case ServerError      = 'general.server_error';
}
