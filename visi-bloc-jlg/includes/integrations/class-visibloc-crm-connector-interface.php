<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

/**
 * Contract used by CRM connectors to exchange data with Visi-Bloc.
 */
interface Visibloc_CRM_Connector_Interface {
    /**
     * Retrieve the list of segments exposed by the remote CRM.
     *
     * Each item should contain an identifier (`id` or `value`) and can provide
     * presentation metadata such as `label`, `description` and `source`.
     *
     * @return array<int, array<string, mixed>>|WP_Error
     */
    public function fetch_segments();

    /**
     * Synchronize the members belonging to a specific segment.
     *
     * Implementations may use this entry point to pre-warm caches or trigger
     * background syncs used by server-side evaluations.
     *
     * @param string $segment_identifier Segment identifier returned by fetch_segments().
     * @return bool|array<mixed>|WP_Error
     */
    public function sync_segment_members( $segment_identifier );
}
