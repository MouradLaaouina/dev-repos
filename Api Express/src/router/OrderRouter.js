import Router from "express"
import { getWithToken, postWithToken, putWithToken, deleteWithToken } from "../service/axiosService.js"
import { responseToDTO } from "../service/responseService.js"
import authMiddleware from "../middleware/authMiddleware.js"

const orderRouter = Router()


///orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=0
orderRouter.get("/", authMiddleware, async (req,res)=>{
    const token = process.env.DOLAPIKEY
    const pageNbr = req.query.page
    const thirparty_ids = req.query.thirdparty_ids
    try{
        let url =`/orders?sortfield=t.rowid&sortorder=ASC&limit=100&page=${pageNbr}`
        if(thirparty_ids){
            url +=`&thirparty_ids=${thirparty_ids}`
        }
        const response = await getWithToken(url,token)
        res.send({
                status:"ok",
                result: responseToDTO(response,null)
            })
    }catch(error){
        console.log(error);
        res.send({
            status:"error"
        })
    }
})

orderRouter.get("/:id", authMiddleware, async (req,res)=>{
    const token = process.env.DOLAPIKEY
    const id = req.params.id
    try{
        const response = await getWithToken(`/orders/${id}`,token)
        res.send({
                status:"ok",
                result: responseToDTO(response,null)
            })
    }catch(error){
          console.log(error);
            res.send({
                status:"error"
            })
    }
})

orderRouter.post("/", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const orderData = req.body;
    try {
        const newOrder = await postWithToken("/orders", orderData, token);
        res.send(newOrder);
    } catch (err) {
        res.status(500).send({ error: "Failed to create order" });
    }
});

orderRouter.put("/:id", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const orderData = req.body;
    const { id } = req.params;
    try {
        const updatedOrder = await putWithToken(`/orders/${id}`, orderData, token);
        res.send(updatedOrder);
    } catch (err) {
        res.status(500).send({ error: "Failed to update order" });
    }
});

orderRouter.delete("/:id", authMiddleware, async (req, res) => {
    const token = process.env.DOLAPIKEY;
    const { id } = req.params;
    try {
        await deleteWithToken(`/orders/${id}`, token);
        res.status(204).send();
    } catch (err) {
        res.status(500).send({ error: "Failed to delete order" });
    }
});

export default orderRouter