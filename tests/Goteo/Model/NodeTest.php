<?php


namespace Goteo\Model\Tests;

use Goteo\TestCase;
use Goteo\Model\Node;
use Goteo\Model\User;
use Goteo\Model\Project;

class NodeTest extends TestCase {
    private static $related_tables = array('project' => 'node',
                    'user' => 'node',
                    'banner' => 'node',
                    'campaign' => 'node',
                    'faq' => 'node',
                    'info' => 'node',
                    'mail' => 'node',
                    'node_data' => 'node',
                    'node_lang' => 'id',
                    'invest_node' => ['user_node', 'project_node', 'invest_node'],
                    'patron' => 'node',
                    'post_node' => 'node',
                    'sponsor' => 'node',
                    'stories' => 'node',
                    );

    private static $data = array('id' => 'testnode2', 'name' => 'Test node 2');

    public function testInstance() {
        \Goteo\Core\DB::cache(false);
        $ob = new Node();

        $this->assertInstanceOf('\Goteo\Model\Node', $ob);

        return $ob;
    }
    /**
     * @depends testInstance
     */
    public function testValidate($ob) {
        $this->assertFalse($ob->validate());
        $this->assertFalse($ob->save());
        //delete test node if exists
        try {
            $node = Node::get(self::$data['id']);
            $node->dbDelete();
        } catch(\Exception $e) {
            // node not exists, ok
        }
    }

    /**
     * @depends testValidate
     */
    public function testCreateNode() {
        $errors = array();
        $node = new Node(self::$data);
        $this->assertTrue($node->validate($errors), print_r($errors, 1));
        $this->assertNotFalse($node->create($errors), print_r($errors, 1));
// die($node->id);
        $node = Node::get($node->id);
        $this->assertInstanceOf('\Goteo\Model\Node', $node);

        $this->assertEquals($node->id, self::$data['id']);
        $this->assertEquals($node->name, self::$data['name']);

        return $node;
    }

    /**
     * @depends testCreateNode
     */
    public function testRenameNode($node) {
        try {
            $node->rebase('test node 3');
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\Goteo\Application\Exception\ModelException', $e);
        }

        $this->assertTrue($node->rebase('testnode3'));
        $this->assertEquals($node->id, 'testnode3');
        return $node;
    }


    /**
     * @depends testRenameNode
     */
    public function testConstrains($node) {
        $testnode = get_test_node();
        try {
            $node->rebase($testnode->id);
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\Goteo\Application\Exception\ModelException', $e);
        }
        delete_test_node();
        $this->assertTrue($node->rebase($testnode->id));
        $u = get_test_user();
        $p = get_test_project();

        $this->assertTrue($node->rebase('testnode2'));
        try {
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\Goteo\Application\Exception\ModelException', $e);
        }
        $user = User::get($u->id);
        $project = Project::get($p->id);
        $this->assertEquals('testnode2', $user->node);
        $this->assertEquals('testnode2', $project->node);
    }

    /**
     * @depends testCreateNode
     */
    public function testDeleteNode($node) {
        try {
            $node->dbDelete();
        }
        catch(\Exception $e) {
            $this->assertInstanceOf('\PDOException', $e);
        }
        delete_test_project();
        delete_test_user();

        $this->assertTrue($node->dbDelete());

        return $node;
    }

    public function testNonExisting() {
        try {
            $ob = Node::get(self::$data['id']);
        }catch(\Exception $e) {
            $this->assertInstanceOf('\Goteo\Application\Exception\ModelNotFoundException', $e);
        }
        try {
            $ob = Node::get('non-existing-project');
        }catch(\Exception $e) {
            $this->assertInstanceOf('\Goteo\Application\Exception\ModelNotFoundException', $e);
        }
    }

    public function testCleanNodeRelated() {
        foreach(self::$related_tables as $tb => $fields) {
            if(!is_array($fields)) $fields = [$fields];
            foreach($fields as $field) {
                $this->assertEquals(0, Node::query("SELECT COUNT(*) FROM `$tb`  WHERE `$field` NOT IN (SELECT id FROM node)")->fetchColumn(), "DB incoherences in table [$tb], Please run SQL command:\nDELETE FROM  `$tb` WHERE `$field` NOT IN (SELECT id FROM node)");
            }
        }
    }

    /**
     * Some cleanup
     */
    static function tearDownAfterClass() {
        delete_test_project();
        delete_test_user();
        delete_test_node();
    }
}
