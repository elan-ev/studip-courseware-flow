<?php
/**
 * CourseFlowsIndex
 *
 * JSON-API-Route zum Abrufen aller Flows einer bestimmten Veranstaltung.
 * Prüft die Berechtigungen des Benutzers und liefert paginierte Ergebnisse.
 *
 * @package   CoursewareFlow\JsonApi\Routes
 * @since     1.0.0
 * @author    Ron Lucke <lucke@elan-ev.de>
 * @copyright 2025 elan e.V.
 * @license   AGPL-3.0
 * @link      https://elan-ev.de
 */

namespace CoursewareFlow\JsonApi\Routes;

use JsonApi\Errors\AuthorizationFailedException;
use JsonApi\Errors\RecordNotFoundException;
use JsonApi\JsonApiController;
use CoursewareFlow\Models\Flow;
use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;

class CourseFlowsIndex extends JsonApiController
{
    protected $allowedPagingParameters = ['offset', 'limit'];

    protected $allowedIncludePaths = ['course'];

    public function __invoke(Request $request, Response $response, $args)
    {
        $user = $this->getUser($request);
        $course = \Course::find($args['id']);

        if (!$course) {
            throw new RecordNotFoundException();
        }

        if (!Authority::canIndexCourseFlows($user, $course)) {
            throw new AuthorizationFailedException();
        }

        list($offset, $limit) = $this->getOffsetAndLimit();

        $resources = Flow::findBySQL('source_course_id = ? ORDER BY mkdate LIMIT ? OFFSET ?', [$course->id, $limit, $offset]);

        return $this->getPaginatedContentResponse($resources, count($resources));
    }
}